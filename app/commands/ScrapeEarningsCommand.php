<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ScrapeEarningsCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'scrape:earnings';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Scrape earnings data';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire() {
		$days = $this->getDatesToScrape();
		$delay = 2;

		foreach($days as $day) {
			$delay += rand(1, 5);
			Queue::later($delay, 'EarningsScraperWorker', ['day' => $day->toDateTimeString()]);
		}
	}

	/**
	 * Generate days to scrape. Either based on date (w/ weekend option) or yesterday.
	 * If yesterday is a weekend it's auto-implied.
	 * For weekends get the previous week's Fri-Sun based on Mon-Sun week.
	 *
	 * @return array
	 */
	public function getDatesToScrape() {
		// Base to scrape from
		if($this->argument('date')) {
			$base = Carbon::parse($this->argument('date'), 'America/Los_Angeles');
		} else {
			$base = Carbon::yesterday('America/Los_Angeles');
		}

		// Get the weekend range if required
		if($this->option('weekend') || (!$this->argument('date') && $base->isWeekend())) {
			if($base->dayOfWeek < Carbon::FRIDAY) {
				return [
					$base->copy()->modify('Friday last week'),
					$base->copy()->modify('Saturday last week'),
					$base->copy()->modify('Sunday last week'),
				];
			} else {
				return [
					$base->copy()->modify('Friday this week'),
					$base->copy()->modify('Saturday this week'),
					$base->copy()->modify('Sunday this week'),
				];
			}
		} else {
			return [$base];
		}
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments() {
		return [
			['date', InputArgument::OPTIONAL, 'Date of which to scrape. Can be anything understood by strtotime', null],
		];
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions() {
		return [
			['weekend', null, InputOption::VALUE_NONE, 'Whether to scrape the whole weekend', null],
		];
	}

}