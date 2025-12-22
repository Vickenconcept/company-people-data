# Quick Setup Guide

## ✅ What's Done

You've migrated the database. Here's what to do next:

## 📋 Next Steps

### 1. Install Dependencies
```bash
composer install
```

### 2. Configure Environment Variables

Add these to your `.env` file:

```env
# Required API Keys
OPENAI_API_KEY=your_openai_key_here
SCRAPERAPI_API_KEY=your_scraperapi_key_here
APOLLO_API_KEY=your_apollo_key_here
HUNTER_API_KEY=your_hunter_key_here

# SMTP Configuration (for email sending)
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_email@example.com
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 3. Start Queue Worker

The system uses queues for async processing. Start the worker:

```bash
php artisan queue:work
```

Or if using Laravel Horizon:
```bash
php artisan horizon
```

### 4. Test the System

#### Create a Lead Request
```bash
POST /api/leads
{
    "reference_company_name": "Nike",
    "reference_company_url": "https://nike.com",
    "target_count": 10,
    "target_job_titles": ["CEO", "CFO"]
}
```

#### Check Status
```bash
GET /api/leads/{id}
```

#### Get Results
```bash
GET /api/leads/{id}/results
```

## 🔑 API Services Used

- **OpenAI**: AI analysis and email generation
- **ScraperAPI**: Website scraping
- **Apollo**: Company search and people discovery
- **Hunter**: Email finding (alternative to Apollo for people search)
- **Laravel Mail**: Email sending via SMTP

## 📝 Notes

- All API routes are protected with `auth` middleware (web session auth)
- API keys can be stored per-user in the database or use `.env` defaults
- Queue jobs handle the heavy lifting asynchronously
- Check `storage/logs/laravel.log` for any errors

## 🚀 Workflow

1. User creates lead request → Queued
2. System scrapes reference company → Queued
3. AI analyzes and creates ICP → Queued
4. Search for similar companies (Apollo) → Queued
5. Find contacts for each company (Apollo/Hunter) → Queued
6. Optional: Generate and send emails → Queued

All steps are processed asynchronously via queue workers.

