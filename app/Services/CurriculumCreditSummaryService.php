<?php

namespace App\Services;

use App\Models\Curriculum;
use Illuminate\Support\Facades\DB;

class CurriculumCreditSummaryService
{
    public function summarize(Curriculum $curriculum): array
    {
        $terms = DB::table('curriculum_terms')
            ->where('curriculum_id', $curriculum->id)
            ->orderBy('sequence_no')
            ->orderBy('id')
            ->get(['id', 'sequence_no', 'name', 'status']);

        $termSummaries = [];
        $curriculumRequiredCredits = 0.0;
        $curriculumMaximumCredits = 0.0;
        $missingCredits = 0;

        foreach ($terms as $term) {
            $slots = DB::table('curriculum_slots')
                ->where('curriculum_term_id', $term->id)
                ->where('status', 'ACTIVE')
                ->orderBy('display_order')
                ->orderBy('id')
                ->get([
                    'id',
                    'name',
                    'credits',
                    'credit_counting',
                    'selection_mode',
                    'min_selection',
                    'max_selection',
                    'status',
                ]);

            $requiredCredits = 0.0;
            $maximumCredits = 0.0;
            $slotRows = [];

            foreach ($slots as $slot) {
                $credits = $slot->credits === null
                    ? null
                    : (float) $slot->credits;

                if ($credits === null) {
                    $missingCredits++;
                }

                $creditValue = $credits ?? 0.0;
                $isCountable = strtoupper((string) ($slot->credit_counting ?? 'COUNTABLE')) === 'COUNTABLE';
                $countableCreditValue = $isCountable ? $creditValue : 0.0;
                $selectionMode = strtoupper((string) $slot->selection_mode);

                if ($selectionMode === 'CHOICE') {
                    $min = (int) ($slot->min_selection ?? 0);
                    $max = (int) ($slot->max_selection ?? 0);

                    $slotRequiredCredits = $countableCreditValue * $min;
                    $slotMaximumCredits = $countableCreditValue * $max;
                } else {
                    $slotRequiredCredits = $countableCreditValue;
                    $slotMaximumCredits = $countableCreditValue;
                }

                $requiredCredits += $slotRequiredCredits;
                $maximumCredits += $slotMaximumCredits;

                $slotRows[] = [
                    'id' => (int) $slot->id,
                    'name' => $slot->name,
                    'credits' => $credits,
                    'credit_counting' => $slot->credit_counting ?? 'COUNTABLE',
                    'selection_mode' => $slot->selection_mode,
                    'min_selection' => $slot->min_selection,
                    'max_selection' => $slot->max_selection,
                    'required_credits' => round($slotRequiredCredits, 2),
                    'maximum_credits' => round($slotMaximumCredits, 2),
                ];
            }

            $requiredCredits = round($requiredCredits, 2);
            $maximumCredits = round($maximumCredits, 2);

            $curriculumRequiredCredits += $requiredCredits;
            $curriculumMaximumCredits += $maximumCredits;

            $termSummaries[] = [
                'id' => (int) $term->id,
                'sequence_no' => (int) $term->sequence_no,
                'name' => $term->name,
                'status' => $term->status,
                'required_credits' => $requiredCredits,
                'maximum_credits' => $maximumCredits,
                'slots' => $slotRows,
            ];
        }

        return [
            'terms' => $termSummaries,
            'curriculum_required_credits' => round(
                $curriculumRequiredCredits,
                2
            ),
            'curriculum_maximum_credits' => round(
                $curriculumMaximumCredits,
                2
            ),
            'missing_credit_slots' => $missingCredits,
        ];
    }
}
