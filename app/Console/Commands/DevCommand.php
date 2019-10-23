<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\ArrayToXml\ArrayToXml;

class DevCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dev Command';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $array = [
            'Title' => 'This is the title',
            'Prompt' => 'Prompt text goes here',
            'URL' => 'The target URL for the completed input goes here',
            'InputItem' => [
                'DisplayName' => 'Name of input field to display',
                'QueryStringParam' => 'The parameter to be added to the target URL',
                'DefaultValue' => 'Default Value',
                'InputFlags' => 'The flag specifying the type of allowable input'
            ]
        ];
        $result = ArrayToXml::convert($array, 'CiscoIPPhoneInput');
        dd($result);
    }
}
