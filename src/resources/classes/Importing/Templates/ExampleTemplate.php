<?php
/**
 * Created by PhpStorm.
 * User: mihai
 * Date: 22.02.2017
 * Time: 13:51.
 */

namespace App\Importing\Templates;

use LaravelEnso\DataImport\app\Classes\BaseTemplate;

class ExampleTemplate extends BaseTemplate
{
    public $jsonTemplate = '{
		"sheets": [
			{
				"name": "sheet1",
				"columns": [
					{
						"name": "string",
						"laravelValidations": "string",
						"complexValidations": [
							{ "type": "unique_in_column" },
							{ "type": "exists_in_sheet", "sheet": "sheet2", "column": "must_exist_here" }
						],
						"customValidations": [ ]
					},
					{
						"name": "date",
						"laravelValidations": "date",
						"complexValidations": [
							{ "type": "unique_in_column" }
						],
						"customValidations": [ ]
					},
					{
						"name": "email",
						"laravelValidations": "string|email",
						"complexValidations": [
							{ "type": "unique_in_column" }
						],
						"customValidations": [ ]
					}
				]
			},
			{
				"name": "sheet2",
				"columns": [
					{
						"name": "must_exist_here",
						"laravelValidations": "string",
						"complexValidations": [	],
						"customValidations": [ ]
					}
                ]
            }
		]
	}';
}
