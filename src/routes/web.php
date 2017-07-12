<?php

Route::middleware(['web', 'auth', 'core'])
    ->namespace('LaravelEnso\DataImport\app\Http\Controllers')
    ->prefix('import')->as('import.')
    ->group(function () {
        Route::get('', 'DataImportController@index')
            ->name('index');
        Route::delete('{dataImport}', 'DataImportController@destroy')
            ->name('destroy');
        Route::get('getImportData', 'DataImportController@getImportData')
            ->name('getImportData');
        Route::post('run/{type}', 'DataImportController@run')
            ->name('run');
        Route::get('download/{dataImport}', 'DataImportController@download')
            ->name('download');
        Route::get('getSummary/{dataImport}', 'DataImportController@getSummary')
            ->name('getSummary');

        Route::get('initTable', 'DataImportController@initTable')
            ->name('initTable');
        Route::get('getTableData', 'DataImportController@getTableData')
            ->name('getTableData');
        Route::get('exportExcel', 'DataImportController@exportExcel')
            ->name('exportExcel');

        Route::get('getTemplate/{type}', 'ImportTemplateController@getTemplate')
            ->name('getTemplate');
        Route::post('uploadTemplate/{type}', 'ImportTemplateController@upload')
            ->name('uploadTemplate');
        Route::delete('deleteTemplate/{template}', 'ImportTemplateController@destroy')
            ->name('deleteTemplate');
        Route::get('downloadTemplate/{template}', 'ImportTemplateController@download')
            ->name('downloadTemplate');
    });
