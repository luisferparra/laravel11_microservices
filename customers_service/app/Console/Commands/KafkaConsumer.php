<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Kafka\KafkaConsumerService;

class KafkaConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:kafka:consume';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command that consume Kafka Topics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $consumer = new KafkaConsumerService();
        $topics = config("kafka.consumers_topics");
        $consumer->subscribeToTopics($topics);
        while (true) {
            $this->info("Intentando coger un mensaje");

            $consumer->consume(120*1000);
            $message = $consumer->getMessage();
            if ($message!==false) {
                $this->info(json_encode($message));
                $messageDecoded = $consumer->decodeKafkaMessage($message);
                $this->info(json_encode($messageDecoded));
                //We process
                $consumer->messageCommit($message);
            }
        }

    }
}
