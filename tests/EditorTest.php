<?php

use Codenzia\FilamentComments\Forms\Components\Editor;

it('can be instantiated', function () {
    $component = Editor::make('content');

    expect($component)->toBeInstanceOf(Editor::class)
        ->and($component->getView())->toBe('filament-comments::forms.components.editor');
});

it('can set rows', function () {
    $component = Editor::make('content')->rows(5);

    expect($component->getRows())->toBe(5);
});
