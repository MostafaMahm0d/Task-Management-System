<?php

namespace App\Filament\Tenant\Resources\Ratings\Schemas;

use App\Models\Rating;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class RatingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(fn (Rating $record): string => $record->employee?->name ?? 'Rating')
                    ->description(fn (Rating $record): ?string => $record->project?->name)
                    ->icon(Heroicon::OutlinedUsers)
                    ->schema([
                        TextEntry::make('rater.name')
                            ->label('Rated by')
                            ->formatStateUsing(fn (?string $state): string => auth()->user()?->can('rating.manageAll') ? $state : '****'),

                        TextEntry::make('overall_score')
                            ->label('Overall score')
                            ->badge()
                            ->color(fn (?string $state): string => match (true) {
                                $state === null => 'gray',
                                (float) $state >= 4 => 'success',
                                (float) $state >= 3 => 'warning',
                                default => 'danger',
                            }),

                        TextEntry::make('period_start')
                            ->label('Period')
                            ->date()
                            ->placeholder('Not set')
                            ->formatStateUsing(fn (Rating $record): string => $record->period_start && $record->period_end
                                ? "{$record->period_start->format('M j, Y')} – {$record->period_end->format('M j, Y')}"
                                : 'Not set'),
                    ])
                    ->columns(3),

                Section::make('Scores')
                    ->icon(Heroicon::OutlinedStar)
                    ->schema(
                        collect(Rating::metrics())
                            ->map(fn (string $label, string $metric) => TextEntry::make($metric)->label($label)->suffix(' / 5'))
                            ->all()
                    )
                    ->columns(4),

                Section::make('Comments')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->schema([
                        TextEntry::make('comments')
                            ->hiddenLabel()
                            ->placeholder('No comments provided.'),
                    ])
                    ->visible(fn (Rating $record): bool => filled($record->comments)),
            ]);
    }
}
