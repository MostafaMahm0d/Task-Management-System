<?php

namespace App\Filament\Tenant\Resources\Ratings\Schemas;

use App\Models\Rating;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class RatingForm
{
    private const SCORE_OPTIONS = [
        1 => '1 - Poor',
        2 => '2 - Below Average',
        3 => '3 - Average',
        4 => '4 - Good',
        5 => '5 - Excellent',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Evaluation')
                    ->description('Who is being evaluated, and for which project.')
                    ->icon(Heroicon::OutlinedUsers)
                    ->schema([
                        Select::make('employee_id')
                            ->label('Employee')
                            ->relationship('employee', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('project_id')
                            ->label('Project')
                            ->relationship(
                                'project',
                                'name',
                                modifyQueryUsing: fn (Builder $query) => $query->when(
                                    ! auth()->user()->can('project.manageAll'),
                                    fn (Builder $query) => $query->where(
                                        fn (Builder $query) => $query
                                            ->where('owner_id', auth()->id())
                                            ->orWhereHas('members', fn (Builder $query) => $query->whereKey(auth()->id()))
                                    )
                                ),
                            )
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Section::make('Scores')
                    ->description('Rate each metric from 1 (poor) to 5 (excellent).')
                    ->icon(Heroicon::OutlinedStar)
                    ->schema(
                        collect(Rating::metrics())
                            ->map(fn (string $label, string $metric) => Select::make($metric)
                                ->label($label)
                                ->options(self::SCORE_OPTIONS)
                                ->default(3)
                                ->required()
                                ->native(false))
                            ->all()
                    )
                    ->columns(2),

                Section::make('Period & notes')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->schema([
                        DatePicker::make('period_start')
                            ->native(false),

                        DatePicker::make('period_end')
                            ->native(false),

                        Textarea::make('comments')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->columns(1);
    }
}
