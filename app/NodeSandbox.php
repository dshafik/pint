<?php

namespace App;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class NodeSandbox
{
    /**
     * The version of the sandbox.
     *
     * @var string
     */
    const VERSION = '1';

    /**
     * The path to the sandbox.
     *
     * @var string
     */
    protected $path;

    /**
     * Indicates if the sandbox is initialized.
     *
     * @var bool
     */
    protected $initialized = false;

    /**
     * Creates a new node sandbox instance.
     *
     * @param  string
     * @return void
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Ensures the sandbox is initialized.
     *
     * @return void
     */
    public function ensureInitialized()
    {
        if ($this->initialized) {
            return;
        }

        if (! File::exists($this->path.'/version') || File::get($this->path.'/version') !== static::VERSION) {
            File::deleteDirectory($this->path.'/node_modules');
            File::delete($this->path.'/package-lock.json');

            $this->installNodeDependencies();

            File::put($this->path.'/version', static::VERSION);
        }
    }

    /**
     * The sandbox path.
     *
     * @return string
     */
    public function path()
    {
        return $this->path;
    }

    /**
     * Run the given commands.
     *
     * @param  array<int, string>
     * @return \Illuminate\Process\ProcessResult
     */
    public function run($commands)
    {
        $this->ensureInitialized();

        $process = Process::command(implode(' ', $commands))
            ->path($this->path);

        return $process->run();
    }

    /**
     * Install the node dependencies.
     *
     * @return void
     */
    private function installNodeDependencies()
    {
        $commands = [
            'npm install',
        ];

        $result = Process::command(implode(' && ', $commands))
            ->path($this->path)
            ->run();

        if ($result->failed()) {
            $reason = $result->output();

            abort(1, sprintf('Pint was unable to install its node dependencies. Reason: %s', $reason));
        }
    }
}