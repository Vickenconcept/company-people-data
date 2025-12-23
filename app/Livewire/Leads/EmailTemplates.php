<?php

namespace App\Livewire\Leads;

use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Email Templates'])]
class EmailTemplates extends Component
{
    public bool $showModal = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $subject = '';
    public string $body = '';
    public string $custom_context = '';
    public bool $is_default = false;
    public ?string $message = null;
    public string $messageType = 'success';

    public function openModal(?int $id = null): void
    {
        $this->resetForm();
        
        if ($id) {
            $template = EmailTemplate::where('user_id', Auth::id())->findOrFail($id);
            $this->editingId = $template->id;
            $this->name = $template->name;
            $this->subject = $template->subject;
            $this->body = $template->body;
            $this->custom_context = $template->custom_context ?? '';
            $this->is_default = $template->is_default;
        }
        
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->subject = '';
        $this->body = '';
        $this->custom_context = '';
        $this->is_default = false;
        $this->message = null;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:500',
            'body' => 'required|string|max:10000',
            'custom_context' => 'nullable|string|max:2000',
        ]);

        // If setting as default, unset other defaults
        if ($this->is_default) {
            EmailTemplate::where('user_id', Auth::id())
                ->where('id', '!=', $this->editingId)
                ->update(['is_default' => false]);
        }

        if ($this->editingId) {
            $template = EmailTemplate::where('user_id', Auth::id())->findOrFail($this->editingId);
            $template->update([
                'name' => $this->name,
                'subject' => $this->subject,
                'body' => $this->body,
                'custom_context' => $this->custom_context,
                'is_default' => $this->is_default,
            ]);
            $this->message = 'Template updated successfully!';
        } else {
            EmailTemplate::create([
                'user_id' => Auth::id(),
                'name' => $this->name,
                'subject' => $this->subject,
                'body' => $this->body,
                'custom_context' => $this->custom_context,
                'is_default' => $this->is_default,
            ]);
            $this->message = 'Template created successfully!';
        }

        $this->messageType = 'success';
        $this->closeModal();
    }

    public function delete(int $id): void
    {
        $template = EmailTemplate::where('user_id', Auth::id())->findOrFail($id);
        $template->delete();
        $this->message = 'Template deleted successfully!';
        $this->messageType = 'success';
    }

    public function render()
    {
        $templates = EmailTemplate::where('user_id', Auth::id())
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return view('livewire.leads.email-templates', [
            'templates' => $templates,
        ]);
    }
}
