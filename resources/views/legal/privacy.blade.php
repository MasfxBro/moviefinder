{{-- Note: A qualified legal professional must review this Privacy Policy before any commercial launch. --}}
<x-guest-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 text-text">
            <h1 class="text-3xl font-bold">Privacy Policy</h1>
            <p class="text-sm text-muted">Last updated: November 2026</p>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">1. Data We Collect</h2>
                <p>We collect your name, email address, password hash, subscription and payment records (no card data touches our servers), watchlist items, search history (retained for 90 days), usage counters, security logs, title requests, feedback messages, and aggregated non-identifiable search terms.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">2. Purpose & Processors</h2>
                <p>Data is used to provide search, account management, and billing services. Our trusted processors include cloud hosting providers, transactional email services, Midtrans (payment gateway), and Google Gemini (AI processing).</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">3. AI & Gemini Processing Notice</h2>
                <p>Chat and AI search messages are processed by Google Gemini. Please do not submit sensitive personal information in your chat prompts. Our servers never inject personal data into AI prompts.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">4. User Rights & Account Deletion</h2>
                <p>You have the right to access, correct, or delete your personal data. You can delete your account directly from your account settings (F-19), which purges your personal records while keeping anonymized billing records for legal compliance. Unverified accounts are deleted after 14 days.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">5. Cookies & Security</h2>
                <p>We use essential session and CSRF cookies only. YouTube embeds load only after your explicit click using privacy-enhanced mode. In the event of a security breach, we take immediate remedial action and notify affected users without undue delay.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">6. Contact</h2>
                <p>For privacy inquiries or data requests, contact us at {{ app(\App\Services\Settings::class)->get('site.contact_email') ?? 'support@moviefinder.test' }}.</p>
            </section>
        </div>
    </div>
</x-guest-layout>
