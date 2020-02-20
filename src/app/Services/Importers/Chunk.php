<?php

namespace LaravelEnso\DataImport\App\Services\Importers;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LaravelEnso\Core\App\Models\User;
use LaravelEnso\DataImport\App\Contracts\AfterHook;
use LaravelEnso\DataImport\App\Contracts\Authenticates;
use LaravelEnso\DataImport\App\Contracts\Importable;
use LaravelEnso\DataImport\App\Enums\Statuses;
use LaravelEnso\DataImport\App\Jobs\Finalize;
use LaravelEnso\DataImport\App\Jobs\RejectedExport;
use LaravelEnso\DataImport\App\Models\DataImport;
use LaravelEnso\DataImport\App\Services\Template;
use LaravelEnso\DataImport\App\Services\Validators\Validation;
use LaravelEnso\DataImport\App\Services\Validators\Validator;
use LaravelEnso\DataImport\App\Services\Writer\RejectedDump;
use LaravelEnso\Helpers\App\Classes\Obj;

class Chunk
{
    private DataImport $dataImport;
    private Template $template;
    private User $user;
    private Obj $params;
    private string $sheetName;
    private Collection $chunk;
    private int $index;
    private Collection $rejected;
    private Importable $importer;
    private ?Validator $validator;

    public function __construct(
        DataImport $dataImport,
        Template $template,
        User $user,
        Obj $params,
        string $sheetName,
        Collection $chunk,
        int $index
    ) {
        $this->dataImport = $dataImport;
        $this->template = $template;
        $this->user = $user;
        $this->params = $params;
        $this->sheetName = $sheetName;
        $this->chunk = $chunk;
        $this->index = $index;
        $this->rejected = new Collection();
        $this->importer = $this->template->importer($sheetName);
        $this->validator = $this->template->customValidator($sheetName);
    }

    public function run(): void
    {
        $this->auth();

        $this->chunk->filter(fn ($row) => $this->process($row));

        $this->dumpRejected()
            ->updateProgress();

        if ($this->shouldEnd()) {
            $this->finalize();
        }
    }

    private function auth(): void
    {
        if ($this->importer instanceof Authenticates) {
            Auth::onceUsingId($this->user->id);
        }
    }

    private function process(Obj $row): void
    {
        if ($this->validates($row)) {
            $this->import($row);
        }
    }

    private function validates($row): bool
    {
        $rules = $this->template->validationRules($this->sheetName);

        (new Validation($row, $rules, $this->validator, $this->user, $this->params))->run();

        if ($row->isRejected()) {
            $this->rejected->push($row);
        }

        optional($this->validator)->clearErrors();

        return $row->isImportable();
    }

    private function import($row): void
    {
        try {
            $this->importer->run($row, $this->user, $this->params);
        } catch (Exception $exception) {
            $row->set(config('enso.imports.errorColumn'), config('enso.imports.unknownError'));
            $this->rejected->push($row);
            Log::debug($exception->getMessage());
        }
    }

    private function dumpRejected(): self
    {
        $this->rejected->whenNotEmpty(fn ($rejected) => (new RejectedDump(
            $this->dataImport,
            $this->sheetName,
            $rejected,
            $this->index
        ))->handle());

        return $this;
    }

    private function updateProgress(): void
    {
        DB::transaction(function () {
            $this->dataImport = DataImport::whereId($this->dataImport->id)
                ->lockForUpdate()->first();

            $this->dataImport->update([
                'successful' => $this->dataImport->successful + $this->successful(),
                'failed' => $this->dataImport->failed + $this->rejected->count(),
                'processed_chunks' => $this->dataImport->processed_chunks + 1,
            ]);
        });
    }

    private function finalize(): void
    {
        if ($this->importer instanceof AfterHook) {
            $this->importer->after($this->user, $this->params);
        }

        $this->dataImport->setStatus(Statuses::Processed);

        RejectedExport::withChain([new Finalize($this->dataImport)])
            ->dispatch($this->dataImport, $this->user);
    }

    private function successful(): int
    {
        return $this->chunk->count() - $this->rejected->count();
    }

    private function shouldEnd(): bool
    {
        return $this->dataImport->fresh()->isFinalized();
    }
}
