<?php namespace App\Services ;


use \DOMDocument;
  
class OzonetelCollectDtmf {

    private $doc;
    private $collect_dtmf;

    //constructor to have multiple constructors
    function __construct() {
        $a = func_get_args();
        $i = func_num_args();
        if (method_exists($this, $f = '__construct' . $i)) {
            call_user_func_array(array($this, $f), $a);
        }
    }

    function __construct0() {
        $this->doc = new DOMDocument("1.0", "UTF-8");
        $this->collect_dtmf = $this->doc->createElement("collectdtmf");
        $this->doc->appendChild($this->collect_dtmf);
    }

    function __construct3($max_digits, $term_char, $time_out=5000) { //time out in ms
        $this->doc = new DOMDocument("1.0", "UTF-8");
        $this->collect_dtmf = $this->doc->createElement("response");
        $this->collect_dtmf->setAttribute("l", $max_digits);
        $this->collect_dtmf->setAttribute("t", $term_char);
        $this->collect_dtmf->setAttribute("o", $time_out);
        $this->doc->appendChild($this->collect_dtmf);
    }

    public function setMaxDigits($maxDigits) {
        $this->collect_dtmf->setAttribute("l", $maxDigits);
    }

    public function setTermChar($termChar) {
        //if dtmf maxdigits not fixed and variable send termination
        //example if your asking enter amount, user can enter any input 
        // 1 - n number exampe 1 or 20 2000 etc
        //then ask cutomer to enter amount followed by hash set termchar=# 
        //set maxdigits=<maximum number to be allowed>

        $this->collect_dtmf->setAttribute("t", $termChar);
    }

    public function setTimeOut($timeOut) {
        $this->collect_dtmf->setAttribute("o", $timeOut = 5000);
        //time out in ms default is 4000ms,
    }

    public function addPlayText($text, $speed=4, $lang="EN", $quality="best") {

        $play_text = $this->doc->createElement("playtext", $text);
        $play_text->setAttribute("speed", $speed);
        //speed used for voice-rate speed limit form 1-9 
        //if speed 1 plays slow, speed =9 plays fastly, this is only for professional tts
        $play_text->setAttribute("lang", $lang);
        //lang attribute now supports Hindi and Telugu with Kannada lang="TE" lang="KA" lang="HI"
        $play_text->setAttribute("quality", $quality);
        //quality can be best,normal
        $this->collect_dtmf->appendChild($play_text);
    }

    public function addPlayAudio($url, $speed=2) {
        // audio to play
        //$url = 'http://ipadress/welcome.wav'
        //wav file format must be
        //PCM-128kbps-16bit-mono-8khz
        //see http://kookoo.in/index.php/kookoo-docs/audio for audio preparation

        $play_audio = $this->doc->createElement("playaudio", $url);
        $this->collect_dtmf->appendChild($play_audio);
    }

    public function getRoot() {
        return $this->collect_dtmf;
    }

  }

?>
