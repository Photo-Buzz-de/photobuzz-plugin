<?php
namespace PhotoBuzz;

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;

class Raffle
{
    public $raffle_id;
    function __construct($raffle_id)
    {
        $this->raffle_id = $raffle_id;
    }
    function add_participant($name, $email, $image_code,$newsletter=false)
    {
        global $wpdb;
        

        return $wpdb->insert(
            "wp_photobuzz_raffle_participants",
            array(
                "name" => $name,
                "email" => $email,
                "time" => (new Datetime("now", new DateTimeZone("Europe/Berlin")))->format("Y-m-d H:i:s"),
                "raffle_id" => $this->raffle_id,
                "blog_id" => get_current_blog_id(),
                "image_code" => $image_code,
                "newsletter"=>$newsletter,
            )
        );
    }
    function add_newsletter($name,$email){
        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://online.fcbayern.com/newsletter-clientservice/rest/newsletter/',
            // You can set any number of default request options.
            'timeout'  => 2.0,
        ]);
        $client->request("PUT", $email . "/NEWSLETTER_CLUB", [
            "headers" => [
                "Accept-Language" => "de-DE",
                "X-NEWSLETTER-Authorization" => "Token 9FB11120-94A6-4F74-A1DF-D055F3F311B2"
            ],
            "query" => [
                "ip" => "45.129.183.131"
            ],
            'debug' => true
        ]);
    }
    function has_participated($image_code)
    {
        global $wpdb;
        $query = $wpdb->prepare("SELECT * FROM wp_photobuzz_raffle_participants WHERE image_code=%s", $image_code);
        $wpdb->get_results($query);
        return $wpdb->num_rows;
    }
    function echo_csv()
    {
        global $wpdb;
        $query = $wpdb->prepare("SELECT name as Name, email, time as Teilnahmezeit, CONCAT('" . home_url("p/") . "',image_code) as Fotolink FROM wp_photobuzz_raffle_participants WHERE raffle_id=%s", $this->raffle_id);
        $results = $wpdb->get_results($query, ARRAY_A);
        $outstream = fopen("php://output", 'w');
        $headers = array();
        foreach ($wpdb->col_info as $hd) {
            $headers[] = $hd->name;
        }
        fputcsv($outstream, $headers, ";");
        foreach ($results as $result) {

            fputcsv($outstream, $result, ";");
        }
    }
}
