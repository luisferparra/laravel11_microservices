<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Kafka\KafkaConsumerService;
use App\Kafka\KafkaProducerService;


use App\Helpers\KafkaHelper;

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
            $this->info("Esperando Mensaje");

            $consumer->consume(120 * 1000);
            $message = $consumer->getMessage();
            $this->warn("Recogemos Mensaje y Procesamos");
            $this->info(json_encode($message));

            if (!empty($message)) {
                //$this->info(json_encode($message));
                $messageDecoded = $consumer->decodeKafkaMessage($message);
                if ($messageDecoded !== false) {


                    //We process
                    $kafkaHelper = new KafkaHelper();
                    $result = $kafkaHelper->getKafkaMessageAndProcess($messageDecoded);
                    $responseMessage = $kafkaHelper->getResponseMessage();
                    $responseCode = $kafkaHelper->getResponseCodeId();
                    if (!$result) {

                        $this->error($responseCode . " :: " . $responseMessage);
                    } else {
                        $this->comment($responseCode . " :: " . $responseMessage);
                    }

                    $consumer->messageCommit($message);
                } else {
                    $consumer->messageCommit($message);
                }
            }
        }
    }
}
