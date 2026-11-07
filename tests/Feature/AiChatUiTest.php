<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\File;

it('renders panel markup for logged-in users only', function () {
    // Guest sees no AI chat panel markup or AI nav button
    $guestResponse = $this->get('/');
    $guestResponse->assertOk();
    $guestResponse->assertDontSee('ai-nav-button');
    $guestResponse->assertDontSee('ai-chat-panel');
    $guestResponse->assertDontSee('aiChat({ variant: \'panel\'');
    $guestResponse->assertDontSee('Message AI Chat');
    $guestResponse->assertDontSee('AI demo mode: the answers are examples only.');

    // Authenticated user sees AI nav button and AI chat panel markup
    $user = User::factory()->create();
    $authResponse = $this->actingAs($user)->get('/');
    $authResponse->assertOk();
    $authResponse->assertSee('aiChat({ variant: \'panel\'', false);
    $authResponse->assertSee('AI Chat');
    $authResponse->assertSee('Message AI Chat');
    $authResponse->assertSee('AI demo mode: the answers are examples only.');
});

it('sends the required mode with global chat requests', function () {
    $script = File::get(resource_path('js/aiChat.js'));

    expect($script)->toContain("mode: this.variant === 'embedded' ? 'title' : 'global'");
});

it('matches every UX string in AI_BEHAVIOR.md section 9', function () {
    $jsContent = File::get(resource_path('js/aiChat.js'));
    $upgradeCardContent = File::get(resource_path('views/components/upgrade-card.blade.php'));
    $aiChatContent = File::get(resource_path('views/components/ai-chat.blade.php'));
    $aiPanelContent = File::get(resource_path('views/components/ai-chat-panel.blade.php'));
    $demoBannerContent = File::get(resource_path('views/components/ai-demo-banner.blade.php'));
    $quotaMeterContent = File::get(resource_path('views/components/quota-meter.blade.php'));

    // Quota and availability counters
    expect($jsContent)->toContain('AI searches left today')
        ->and($jsContent)->toContain('Smart searches left today')
        ->and($quotaMeterContent)->toContain('AI searches left today')
        ->and($quotaMeterContent)->toContain('Smart searches left today');

    // Places full
    expect($jsContent)->toContain("Today's free AI places are full. The next free places open at \${waveTime} WIB.");

    // Daily limit used
    expect($jsContent)->toContain("You have used today's AI searches. They reset at 00:00 WIB (in about \${hours} hours).")
        ->and($jsContent)->toContain('Pro offers ${proLimit} a day, including Smart searches.')
        ->and($jsContent)->toContain("You have reached today's limit. It resets at 00:00 WIB (in about \${hours} hours).");

    // Allowance used
    expect($jsContent)->toContain("You have used this period's AI allowance. It renews on \${renewalDate}.");

    // Provider unavailable
    expect($jsContent)->toContain('AI search is temporarily unavailable. Normal search still works. Please try again in a few minutes.');

    // Demo banner
    expect($demoBannerContent)->toContain('AI demo mode: the answers are examples only.');

    // Upgrade card strings
    expect($upgradeCardContent)->toContain('Keep searching without waiting')
        ->and($upgradeCardContent)->toContain('Plus and Pro use separate AI capacity, so you can keep searching at any time.')
        ->and($upgradeCardContent)->toContain('See plans')
        ->and($upgradeCardContent)->toContain('Maybe later')
        ->and($upgradeCardContent)->toContain('Looking for a specific clip? Plus and Pro can search the web for matching clips.')
        ->and($upgradeCardContent)->toContain('Pro checks each suggestion against your clues and explains what matches and what does not.');

    // Exit warning modal
    expect($aiPanelContent)->toContain('Leave this chat?')
        ->and($aiPanelContent)->toContain('Your conversation with the AI will be lost.')
        ->and($aiPanelContent)->toContain('Stay')
        ->and($aiPanelContent)->toContain('Leave');

    // Data notice
    expect($jsContent)->toContain("Your messages are processed by Google's Gemini to find titles and clips. Please do not share personal information.")
        ->and($aiChatContent)->toContain("Your messages are processed by Google's Gemini to find titles and clips. Please do not share personal information.");

    // Connection failure & retry
    expect($jsContent)->toContain('The server could not be reached. Please check your connection and try again.')
        ->and($aiChatContent)->toContain('Retry');

    // Progress steps & cancel & timeout
    expect($jsContent)->toContain('Understanding your request…')
        ->and($jsContent)->toContain('Checking titles…')
        ->and($jsContent)->toContain('Finding clips…')
        ->and($jsContent)->toContain('The request timed out. Please try again.')
        ->and($aiChatContent)->toContain('Cancel');
});

it('ensures the upgrade card never uses danger styling', function () {
    $cardPath = resource_path('views/components/upgrade-card.blade.php');
    $content = File::get($cardPath);

    // Verify raw file does not contain danger/alarm tokens
    expect($content)->not->toMatch('/\b(danger|bg-danger|text-danger|border-danger|warning|red-\d+|yellow-\d+)\b/i');

    // Render component and verify HTML output
    $rendered = (string) $this->blade('<x-upgrade-card />');
    expect($rendered)->not->toMatch('/\b(danger|bg-danger|text-danger|border-danger|warning|red-\d+|yellow-\d+)\b/i')
        ->and($rendered)->toContain('bg-accent-soft')
        ->and($rendered)->toContain('text-accent')
        ->and($rendered)->toContain('See plans')
        ->and($rendered)->toContain('Maybe later');
});

it('ensures Free users never see the places count', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/ai/status');
    $response->assertOk();

    $quota = $response->json('quota');
    expect($quota['plan'])->toBe('free');

    // The response must never contain the number of places left
    expect($quota)->not->toHaveKey('places_left')
        ->and($quota)->not->toHaveKey('places_count')
        ->and($quota)->not->toHaveKey('free_places_count')
        ->and($quota)->not->toHaveKey('free_places_left')
        ->and($quota)->not->toHaveKey('places_remaining');

    // The rendered views must not show places count
    $quotaView = (string) $this->blade('<x-quota-meter :units-left="3" :units-limit="5" plan="free" />');
    expect($quotaView)->toContain('3 of 5 AI searches left today')
        ->and($quotaView)->not->toMatch('/places?\s+(left|count|remaining)/i');
});
