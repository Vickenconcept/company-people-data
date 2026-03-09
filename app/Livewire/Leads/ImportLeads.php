<?php

namespace App\Livewire\Leads;

use App\Models\Company;
use App\Models\LeadRequest;
use App\Models\LeadResult;
use App\Models\Person;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app', ['title' => 'Import Leads'])]
class ImportLeads extends Component
{
    use WithFileUploads;

    public $csvFile;
    public ?string $message = null;
    public string $messageType = 'success';
    public bool $isImporting = false;
    public int $importedCount = 0;

    protected function rules()
    {
        return [
            'csvFile' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
        ];
    }

    public function import()
    {
        $this->validate();

        $this->isImporting = true;
        $this->message = null;
        $this->importedCount = 0;

        try {
            $path = $this->csvFile->getRealPath();
            $file = fopen($path, 'r');
            
            // Skip header row
            $headers = fgetcsv($file);
            
            // Expected headers (flexible): Company Name, Contact Name, Email, Title, Phone, Industry, Website
            $expectedHeaders = ['company_name', 'contact_name', 'email', 'title', 'phone', 'industry', 'website'];
            
            // Map headers to indices
            $headerMap = [];
            foreach ($expectedHeaders as $expected) {
                $index = array_search(strtolower(str_replace(' ', '_', $expected)), array_map('strtolower', array_map(function($h) {
                    return str_replace(' ', '_', $h);
                }, $headers)));
                if ($index !== false) {
                    $headerMap[$expected] = $index;
                }
            }

            if (empty($headerMap)) {
                throw new \Exception('CSV file must contain at least one of: Company Name, Contact Name, Email');
            }

            // Create a dummy lead request for imported leads
            $leadRequest = LeadRequest::create([
                'user_id' => Auth::id(),
                'reference_company_name' => 'Imported Leads',
                'reference_company_url' => null,
                'target_count' => 0,
                'target_job_titles' => [],
                'status' => 'completed',
                'companies_found' => 0,
                'contacts_found' => 0,
            ]);

            $companiesFound = 0;
            $contactsFound = 0;
            $errors = [];

            while (($row = fgetcsv($file)) !== false) {
                try {
                    // Get data from row based on header map
                    $companyName = isset($headerMap['company_name']) ? trim($row[$headerMap['company_name']] ?? '') : '';
                    $contactName = isset($headerMap['contact_name']) ? trim($row[$headerMap['contact_name']] ?? '') : '';
                    $email = isset($headerMap['email']) ? trim($row[$headerMap['email']] ?? '') : '';
                    $title = isset($headerMap['title']) ? trim($row[$headerMap['title']] ?? '') : '';
                    $phone = isset($headerMap['phone']) ? trim($row[$headerMap['phone']] ?? '') : '';
                    $industry = isset($headerMap['industry']) ? trim($row[$headerMap['industry']] ?? '') : '';
                    $website = isset($headerMap['website']) ? trim($row[$headerMap['website']] ?? '') : '';

                    // Skip if no company name or contact name
                    if (empty($companyName) && empty($contactName)) {
                        continue;
                    }

                    // Find or create company
                    $company = null;
                    if (!empty($companyName)) {
                        $company = Company::firstOrCreate(
                            ['name' => $companyName],
                            [
                                'industry' => $industry ?: null,
                                'website' => $website ?: null,
                            ]
                        );
                        if ($company->wasRecentlyCreated) {
                            $companiesFound++;
                        }
                    }

                    // Find or create person (only if we have a company)
                    $person = null;
                    if ($company && (!empty($contactName) || !empty($email))) {
                        $personData = [
                            'company_id' => $company->id,
                        ];
                        if (!empty($contactName)) {
                            $personData['full_name'] = $contactName;
                        }
                        if (!empty($email)) {
                            $personData['email'] = $email;
                        }
                        if (!empty($title)) {
                            $personData['title'] = $title;
                        }
                        if (!empty($phone)) {
                            $personData['phone'] = $phone;
                        }

                        if (!empty($email)) {
                            $person = Person::firstOrCreate(
                                ['email' => $email, 'company_id' => $company->id],
                                $personData
                            );
                        } elseif (!empty($contactName)) {
                            $person = Person::firstOrCreate(
                                ['full_name' => $contactName, 'company_id' => $company->id],
                                $personData
                            );
                        }

                        if ($person && $person->wasRecentlyCreated) {
                            $contactsFound++;
                        }
                    }

                    // Create lead result if we have company or person
                    if ($company || $person) {
                        LeadResult::create([
                            'lead_request_id' => $leadRequest->id,
                            'company_id' => $company?->id,
                            'person_id' => $person?->id,
                            'status' => 'pending',
                            'similarity_score' => 0,
                        ]);
                        $this->importedCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = 'Row error: ' . $e->getMessage();
                    Log::error('CSV import row error', [
                        'error' => $e->getMessage(),
                        'row' => $row,
                    ]);
                }
            }

            fclose($file);

            // Update lead request with counts
            $leadRequest->update([
                'companies_found' => $companiesFound,
                'contacts_found' => $contactsFound,
            ]);

            $this->message = "Successfully imported {$this->importedCount} lead(s)! Companies: {$companiesFound}, Contacts: {$contactsFound}.";
            $this->messageType = 'success';

            if (!empty($errors)) {
                $this->message .= ' Some rows had errors. Check logs for details.';
            }
        } catch (\Exception $e) {
            Log::error('CSV import failed', ['error' => $e->getMessage()]);
            $this->message = 'Import failed: ' . $e->getMessage();
            $this->messageType = 'error';
        } finally {
            $this->isImporting = false;
            $this->csvFile = null;
        }
    }

    public function render()
    {
        return view('livewire.leads.import-leads');
    }
}

