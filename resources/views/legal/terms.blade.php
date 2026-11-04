{{-- Note: A qualified legal professional must review these Terms before any commercial launch. --}}
<x-guest-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 text-text">
            <h1 class="text-3xl font-bold">Terms of Service</h1>
            <p class="text-sm text-muted">Last updated: November 2026</p>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">1. Service Description</h2>
                <p>MovieFinder is a search and guidance platform. We do not host, stream, or store video files. We provide descriptive metadata, AI-powered scene search, and official links to licensed platforms.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">2. Acceptable Use</h2>
                <p>Users agree not to scrape our platform, abuse AI features, circumvent rate limits or usage quotas, or use the service for any unlawful purposes.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">3. AI Limitations</h2>
                <p>Our AI-powered search and chat features use Google Gemini and search grounding. AI results may occasionally contain inaccuracies. Users must verify details before relying on them.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">4. Subscription Terms & Payments</h2>
                <p>Paid plans (Plus and Pro) renew automatically monthly unless cancelled. You can cancel your subscription at any time within your account settings in two clicks; access continues until the end of the paid period. Upgrades take effect immediately without proration; downgrades apply at the next renewal cycle. Pay-for-30-days plans do not auto-renew.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">5. YouTube & Third-Party Services</h2>
                <p>Video previews are powered by YouTube embeds. By using our video preview features, you agree to be bound by the <a href="https://www.youtube.com/t/terms" target="_blank" rel="noopener" class="text-accent underline">YouTube Terms of Service</a>, and acknowledge that Google's <a href="https://policies.google.com/privacy" target="_blank" rel="noopener" class="text-accent underline">Privacy Policy</a> applies to that content. We do not control third-party websites or external links.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">6. Intellectual Property & Takedowns</h2>
                <p>We respect intellectual property rights. If you believe your rights have been infringed, please contact our copyright team at {{ app(\App\Services\Settings::class)->get('site.copyright_email') ?? 'copyright@moviefinder.test' }} or use our in-app reporting tools.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">7. Limitation of Liability & Contact</h2>
                <p>The service is provided "as is" without warranties of any kind. For support or legal inquiries, contact us at {{ app(\App\Services\Settings::class)->get('site.contact_email') ?? 'support@moviefinder.test' }}.</p>
            </section>
        </div>
    </div>
</x-guest-layout>
