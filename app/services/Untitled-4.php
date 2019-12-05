<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Document</title>
    </head>
    <style type="text/css">
      body{
        font-family: 'Montserrat', sans-serif;
      }
    </style>
    <body>
        <?php
            $base_url = Config::get('app.website');
            $pass_type = 'red';

            if(!empty($pass['pass_type']) && $pass['pass_type'] == 'black'){
                $pass_type = $pass['pass_type'];
            }
            
            if(!empty($pass['pass_type']) && $pass['pass_type'] == 'hybrid' && !empty($pass['corporate'])){ 
                $corporate_name = strtolower($pass['corporate']);
                $faq_url = $base_url.'/onepass/pass/'.$corporate_name.'#faq-title';
                $tnc_url = $base_url.'/onepass/pass/'.$corporate_name.'#feature-title';
            }
            else {
                $faq_url = $base_url.'/onepass/pass?passtype='.$pass_type.'#faq-title';
                $tnc_url = $base_url.'/onepass/pass?passtype='.$pass_type.'#feature-title';
            }
        ?>
       <table class="email-container" style="background:#ffffff none no-repeat center/cover;background-color:#ffffff;background-image:none;background-repeat:no-repeat;/* background-position:center; */background-size:cover;border-top:0;border-bottom:0;padding-bottom:0;background-color:#fff;font-size: 18px; border-spacing: 0px;width:600px;margin: 0 auto; border: 1px solid #d4d4d4;border-radius: 5px">
          <tr>
             <td style="padding: 0px;"> 
             <table style="border-spacing: 0;background-color:#fff" align="center">
                 <tbody align="center">
                     <tr>
                         <td align="center" style="padding: 0;">
                            <a href="#"><img src="https://b.fitn.in/global/mailer/onepass-intro/1.jpg"></a>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top" align="center" style="padding: 10px 60px; background-color: #ffffff !important; ">
                            <p style="color:orange;line-height: 1.5; font-weight: 600;">
                                Hey {{$customer_name}},
                            </p>
                            <p style="color:orange;line-height: 1.5; font-weight: 600;">
                                Welcome to the OnePass FitFam! You’ve made a great decision by buying a OnePass. Why do we say this? Read on to find out 
                            </p>
                        </td>
                     </tr>
                     <tr>
                        <td valign="top" align="right" style="padding: 0px;">
                            <a href="#"><img src="https://b.fitn.in/global/mailer/onepass-intro/2.jpg"></a>
                        </td>
                     </tr>
                     <tr>
                        <td valign="top" align="center" style="padding: 15px 25px;">
                            <table style="border-spacing: 0;" align="center">
                                <tr>
                                    <td valign="top" style="width: 5%;padding-top: 5px;"><img src="https://b.fitn.in/global/mailer/onepass-intro/3.jpg" alt=""></td>
                                    <td valign="top" style="color: #3b4674;font-weight: 600;padding-bottom: 20px;">
                                    
                                        <?php
                                            if($pass_type =='hybrid') {
                                                echo "Your single point access to ALL the gyms and fitness studios in Fitternity’s partner network (that’s 12,000 of them *wow*)";
                                            }
                                            else if($pass_type == 'red') {
                                                echo "Your single point limitless access to ALL the gyms and fitness studios in Fitternity’s partner network (that’s 12,000 of them *wow*)
                                                ";
                                            }
                                            else if( $pass_type == 'black') {
                                                echo "Your single point limitless access to ALL the gyms and fitness studios in Fitternity’s partner network (that’s 12,000 of them *wow*)";
                                            }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="top" style="width: 5%;padding-top: 5px;"><img src="https://b.fitn.in/global/mailer/onepass-intro/3.jpg" alt=""></td>
                                    <td valign="top" style="color: #3b4674;font-weight: 600;padding-bottom: 20px;">Explore 17+ different workout forms like CrossFit, MMA, Spinning, Swimming at luxury hotels & more till you aren’t sure of which workout forms works best for you and then fix up a workout 
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="top" style="width: 5%;padding-top: 5px;"><img src="https://b.fitn.in/global/mailer/onepass-intro/3.jpg" alt=""></td>
                                    <td valign="top" style="color: #3b4674;font-weight: 600;padding-bottom: 20px;">Exclusive access to luxury swimming pools, sports etc because you’re a beloved OnePass member (Feeling special, eh? :p)
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="top" style="width: 5%;padding-top: 5px;"><img src="https://b.fitn.in/global/mailer/onepass-intro/3.jpg" alt=""></td>
                                    <td valign="top" style="color: #3b4674;font-weight: 600;padding-bottom: 20px;">Your fitness membership is as flexible as you aspire to be (yoga, FTW) with easy hassle-free booking and cancellation
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="top" style="width: 5%;padding-top: 5px;"><img src="https://b.fitn.in/global/mailer/onepass-intro/3.jpg" alt=""></td>
                                    <td valign="top" style="color: #3b4674;font-weight: 600;padding-bottom: 20px;">Be a part of a like-minded fitness community with 1000+ members working out near you
                                    </td>
                                </tr>
                                <?php
                                    if($pass_type == 'black'){
                                        echo '<tr>
                                            <td valign="top" style="width: 5%;padding-top: 5px;"><img src="https://b.fitn.in/global/mailer/onepass-intro/3.jpg" alt=""></td>
                                            <td valign="top" style="color: #000;font-weight: 600;padding-bottom: 20px;">
                                                The best thing, your membership doesn’t expire till you exhaust your sessions
                                            </td>
                                        </tr>';
                                    }
                                ?> 
                                
                            </table>
                        </td>
                     </tr>
                     <tr>
                        <td valign="top" align="left" style="padding: 0px;">
                            <a href="#"><img src="https://b.fitn.in/global/mailer/onepass-intro/4.jpg"></a>
                            <p style="color:orange;line-height: 0;font-size: 30px;padding-left: 20px; font-weight: 600;">(To Your OnePass & Us)</p>
                            <div style="padding: 0px 20px 20px;font-weight: 600;color: #3b4674;">
                                <p style="line-height: 1.2;">Share your OnePass journey on your Instagram handle as a video (post, story or both :p) & review us on App/Play Store & <span style="color: orange;">win yourself Limited Edition - Marvel Universe Workout Gear worth INR 3,500!</span> </p>
                                <p style="line-height: 1.2;">Don't forget to follow and tag @fitternity and use #OnePassXMe & #FitWithFitternity </p>
                                <p style="line-height: 0;">Give us a Follow: <a style="color: orange;" href="https://www.instagram.com/fitternity/">https://www.instagram.com/fitternity/</a></p>
                                <p style="line-height: 0.5;">Review us: <a style="color: orange;" href="https://www.fitternity.com/getfitternityapp">https://www.fitternity.com/getfitternityapp</a></p>
                            </div>
                        </td>
                     </tr>
                     <tr>
                        <td valign="top" align="left" style="padding: 0px;">
                            <a href="#"><img style="float: right;margin-bottom: 20px;" src="https://b.fitn.in/global/mailer/onepass-intro/5.jpg"></a>
                            <p style="color:orange;line-height: 1;font-size: 30px;padding-left: 20px;padding-right: 100px; font-weight: 600;">Great, you’ve bought a OnePass, now what?</p>
                            <a href="#"><img src="https://b.fitn.in/global/mailer/onepass-intro/6.jpg" alt=""></a>
                            <a href="#"><img src="https://b.fitn.in/global/mailer/onepass-intro/7.jpg" alt=""></a>
                        </td>
                     </tr>
                     <tr>
                         <td>
                             <table style="margin-top: 20px;">
                                 <tr>
                                     <td><a href="#"><img src="https://b.fitn.in/global/mailer/onepass-intro/8.jpg" alt=""></a></</td>
                                     <td valign="top">
                                         <p style="color: #3b4674;font-weight: 600;line-height: 1.5;">We have a specialized Fitness concierge set up just for you. For all things OnePass please reach out on - 7400062845 or 7400062849</p>
                                    </td>
                                 </tr>
                             </table>
                         </td>
                     </tr>
                     <tr>
                         <td><a href="#"><img src="https://b.fitn.in/global/mailer/onepass-intro/9.jpg" alt=""></a></td>
                     </tr>
                     <tr>
                         <td align="center">
                            <p style="color:orange;line-height: 1;font-size: 30px; font-weight: 600;">You Should Know This</p>
                         </td>
                     </tr>
                     <tr>
                        <td valign="top" align="center" style="padding: 15px 25px;">
                            <table style="border-spacing: 0;" align="center">
                                <tr>
                                    <td valign="top" style="width: 5%;padding-top: 5px;"><img src="https://b.fitn.in/global/mailer/onepass-intro/3.jpg" alt=""></td>
                                    <td valign="top" style="color: #3b4674;font-weight: 600;padding-bottom: 20px;">You can cancel your OnePass if you aren’t satisfied (no questions asked) You won’t do this, we know! But still <a href={{$tnc_url}}> click for details</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="top" style="width: 5%;padding-top: 5px;"><img src="https://b.fitn.in/global/mailer/onepass-intro/3.jpg" alt=""></td>
                                    <td valign="top" style="color: #3b4674;font-weight: 600;padding-bottom: 20px;">You can use your OnePass as a fitness membership at one/many dedicated places* <a href={{$tnc_url}}> Click for details.</a>                                            
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="top" style="width: 5%;padding-top: 5px;"><img src="https://b.fitn.in/global/mailer/onepass-intro/3.jpg" alt=""></td>
                                    <td valign="top" style="color: #3b4674;font-weight: 600;padding-bottom: 20px;">Have more questions?  <span style="color: orange;"><a href={{$faq_url}} target="_blank">Check out FAQs on our platform</a></span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                     </tr>
                     <tr>
                        <td>
                            <a href="https://www.instagram.com/fitternity/"><img src="https://b.fitn.in/global/mailer/onepass-intro/10.jpg" alt=""></a>
                        </td>
                    </tr>
                 </tbody>
             </table>
        </td>
      </tr>
    </table>
    </body>
    </html>