<?php

use Indatus\Dispatcher\Scheduling\ScheduledCommand;
use Indatus\Dispatcher\Scheduling\Schedulable;
use Indatus\Dispatcher\Drivers\Cron\Scheduler;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
// use Mail;


class BooktrialsummaryCommand extends ScheduledCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'jay:findebooktiraldailysummary';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Send Booktrial Summary Daily To Finder';

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
	 * When a command should run
	 *
	 * @param Scheduler $scheduler
	 * @return \Indatus\Dispatcher\Scheduling\Schedulable
	 */
	public function schedule(Schedulable $scheduler)
	{

		//every day at 2:00am
        //return $scheduler->daily()->hours(2)->minutes(0);

        //every min
		return $scheduler->everyMinutes(1);
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{

		// $email_template = 'emails.testemail1';
		// $email_template_data = array();
		// $email_message_data = array(
		// 	'to' => 'sanjay.id7@gmail.com',
		// 	'reciver_name' => 'sanjay sahu',
		// 	'bcc_emailids' => array(),
		// 	'email_subject' => 'Testemail 4m local ' .time()
		// 	);

		// Mail::queue($email_template, $email_template_data, function($message) use ($email_message_data){
		// 	$message->to($email_message_data['to'], $email_message_data['reciver_name'])
		// 	->bcc($email_message_data['bcc_emailids'])
		// 	->subject($email_message_data['email_subject'].' send email from instant -- '.date( "Y-m-d H:i:s", time()));
		// });



		$finder = Finder::where('_id','=',1)->first();
		Log::info('Send Booktrial Summary Daily To Finder It works ...!'. $finder->slug);

		Log::info('Send Booktrial Summary Daily To Finder It works ...!');
	}

	

}
