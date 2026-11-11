# Vibey Leads

The CRM we run Vibey World on. It tracks a project from first contact to delivery: leads come in, turn into clients, clients get projects, and every project carries its documents, meetings and reminders. Clients get their own portal to follow progress and download files.

**What's in it**

- Lead pipeline, clients, projects, documents, meetings and reminders
- Separate client portal with its own login and layout
- AI lead analysis and one-click proposal drafts (Gemini)
- Analytics dashboards (ApexCharts) and PDF generation (dompdf)
- Team roles, Google Calendar connection, Cloudinary uploads, email notifications

**Stack:** Vue 3 + TypeScript, Pinia, TanStack Table, Tailwind and reka-ui (shadcn-vue) on the front; Laravel 12, Sanctum and MySQL on the back.

## Run it locally

```bash
# API
cd server
cp .env.example .env        # fill in DB, and SEED_PASSWORD for the seeded accounts
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve           # http://localhost:8000

# Web app
cd ../client
npm install
echo "VITE_API_BASE_URL=http://localhost:8000/api" > .env
npm run dev                 # http://localhost:5173
```

More detail on the API and data model is in [`server/docs`](server/docs). Note that `ARCHITECTURE.md` is the original plan and is older than the code.
