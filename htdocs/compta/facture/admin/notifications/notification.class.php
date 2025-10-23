<?php

require "providers/Requests.php";
Requests::register_autoloader();

define(SLACK_CHANNEL, 'https://hooks.slack.com/services/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');

class Notification {

    public function getChannel() {
        if (!defined('SLACK_CHANNEL'))
            define(SLACK_CHANNEL, 'https://hooks.slack.com/services/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');
        return SLACK_CHANNEL;
    }

    public function send_slack_notification($folio, $url, $tipo, $fecha, $cliente, $monto){
        global $conf;

        $channel_path = $this->getChannel();

        $content = '';
        try {
            $data = $this->build_slack_model($folio, $url, $tipo, $fecha, $cliente, $monto);

            //print json_encode($data);

            $req = Requests::post($channel_path, array('Content-type' => 'application/json'), json_encode($data));
            return $req->status_code == 200;
        } catch (\Exception $e) {
            return true;
        }
    }

    public function build_slack_model($factFolio, $url, $tipo, $fecha, $cliente, $monto) {
        return array(
            'blocks' =>
                array(
                    0 =>
                        array(
                            'type' => 'section',
                            'text' =>
                                array(
                                    'type' => 'mrkdwn',
                                    'text' => "Se ha creado una nueva factura: \n*$factFolio*",
                                ),
                        ),
                    1 =>
                        array(
                            'type' => 'section',
                            'fields' =>
                                array(
                                    0 =>
                                        array(
                                            'type' => 'mrkdwn',
                                            'text' => "*Type:*\n$tipo",
                                        ),
                                    1 =>
                                        array(
                                            'type' => 'mrkdwn',
                                            'text' => "*Fecha:*\n$fecha",
                                        ),
                                    2 =>
                                        array(
                                            'type' => 'mrkdwn',
                                            'text' => "*Cliente:*\n$cliente",
                                        ),
                                    3 =>
                                        array(
                                            'type' => 'mrkdwn',
                                            'text' => "*Importe:*\n\$$monto",
                                        ),
                                    4 =>
                                        array(
                                            'type' => 'mrkdwn',
                                            'text' => "*Ingresa a :*\n$url",
                                        ),
                                ),
                        ),
                ),
        );
    }
}
//$not = new Notification();
//$not->send_slack_notification();