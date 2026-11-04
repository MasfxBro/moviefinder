{{-- Note: A qualified legal professional must review this Copyright Policy before any commercial launch. --}}
<x-guest-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 text-text">
            <h1 class="text-3xl font-bold">Copyright & Takedown Policy</h1>
            <p class="text-sm text-muted">Last updated: November 2026</p>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">1. Hosting Policy</h2>
                <p>MovieFinder does not host, upload, or store video files, movie copies, or pirated content. We provide structured metadata, search indexing, and official embedding links to authorized third-party platforms (such as YouTube).</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">2. Reporting Infringement</h2>
                <p>Rights holders may report allegedly infringing links by emailing our copyright agent at {{ app(\App\Services\Settings::class)->get('site.copyright_email') ?? 'copyright@moviefinder.test' }} or by using the in-app report button on any clip or title page.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">3. Notice Requirements</h2>
                <p>Takedown notices should include: identification of the copyrighted work, the exact URL or link on MovieFinder, proof of ownership or authorization, and your contact information.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">4. Our Commitment & Action</h2>
                <p>We review takedown notices within a few business days. Reported links are immediately marked as reported and excluded from embedding. Confirmed infringing channels are blocked from our allowlist, and all actions are recorded in our audit logs.</p>
            </section>
        </div>
    </div>
</x-guest-layout>
