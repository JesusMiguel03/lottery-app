<?php

use Illuminate\Support\Facades\Route;
use Spatie\LaravelMarkdown\MarkdownRenderer;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/admin/docs', function () {
    $content = file_get_contents(base_path() . '/docs/documentation.md');

    $html = app(MarkdownRenderer::class)->toHtml($content);

    return view('docs', ['content' => $html]);
});
