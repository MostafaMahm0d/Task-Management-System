<?php

namespace App\Filament\Tenant\Resources\Projects\Pages;

use App\Filament\Tenant\Actions\ViewActivityAction;
use App\Filament\Tenant\Resources\Projects\ProjectResource;
use App\Filament\Tenant\Widgets\ProjectOverviewWidget;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;
use Relaticle\Comments\Filament\Actions\CommentsAction;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CommentsAction::make(),
            ViewActivityAction::make(),
            EditAction::make(),
        ];
    }

    // ViewRecord overrides content() itself (infolist + relation managers only),
    // which bypasses the generic header/footer widgets slot that plain Page
    // classes render via the panel template — so the widget is embedded directly
    // in the content schema here instead of through getHeaderWidgets().
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Livewire::make(ProjectOverviewWidget::class, ['record' => $this->getRecord()])
                ->key('project-overview-widget'),
            $this->hasInfolist()
                ? $this->getInfolistContentComponent()
                : $this->getFormContentComponent(),
            $this->getRelationManagersContentComponent(),
        ]);
    }
}
