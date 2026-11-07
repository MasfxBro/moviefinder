<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\DTOs\CandidateData;
use App\Models\Title;

class CandidateCritic
{
    /** @param array<int, array{candidate: CandidateData, title: Title}> $candidates @return array<int, array{candidate: CandidateData, title: Title}> */
    public function reorder(array $candidates, string $clues): array
    {
        $tokens = array_values(array_filter(preg_split('/[^\pL\pN]+/u', mb_strtolower($clues)) ?: [], fn (string $token): bool => mb_strlen($token) > 2));

        foreach ($candidates as &$item) {
            $title = $item['title'];
            $overview = mb_strtolower((string) $title->overview);
            $year = $title->release_year !== null && preg_match('/\b'.preg_quote((string) $title->release_year, '/').'\b/', $clues) === 1;
            $cast = $title->people->where('role', 'cast')->pluck('name')->all();
            $matched = array_values(array_filter($tokens, fn (string $token): bool => str_contains($overview, $token) || collect($cast)->contains(fn (string $name): bool => str_contains(mb_strtolower($name), $token))));
            $candidate = $item['candidate'];
            $reason = $matched === [] ? 'The overview and cast do not confirm the remembered clues.' : 'Matches: '.implode(', ', array_slice($matched, 0, 4)).'.';
            if ($year) {
                $reason .= ' The year also matches.';
            }
            $item['candidate'] = new CandidateData($candidate->title, $candidate->year, $candidate->mediaType, $candidate->confidence, mb_substr($reason, 0, 200));
            $item['_score'] = count($matched) + (int) $year;
        }
        unset($item);

        usort($candidates, fn (array $a, array $b): int => $b['_score'] <=> $a['_score']);
        foreach ($candidates as &$item) {
            unset($item['_score']);
        }
        unset($item);

        return $candidates;
    }
}
