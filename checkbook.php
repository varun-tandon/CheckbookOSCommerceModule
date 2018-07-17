<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2010 osCommerce

  Released under the GNU General Public License
*/

/*

Update from Tuesday, July 10

What's working: 
- tingle modal
- button and replacing button after auth
- sending check using authenticated user data
- using data from the installation fields
- verifying that the authentication took place
- API secret key via local file on server

What Needs to be Fixed:
- ensuring that all the data on the OSCommerce side is updated and ready to go
- perhaps there's a way to create the files via installation?


*/





  class checkbook {
    var $code, $title, $description, $enabled;

// class constructor
    function checkbook() {
      global $order;
      $this->signature = 'checkbook';
      $this->api_version = '2.0';

      $this->code = 'checkbook';
      $this->title = 'Checkbook.io';
      $this->public_title = 'Checkbook.io';
      $this->description = 'Allow users of your site to make payments via digital check using Checkbook.io. ';
      $this->sort_order = MODULE_PAYMENT_CHECKBOOK_CC_SORT_ORDER;
      $this->enabled = ((MODULE_PAYMENT_CHECKBOOK_STATUS == 'True') ? true : false);
      $this->client_id = MODULE_PAYMENT_CHECKBOOK_CLIENT_ID;
      $this->api_secret = MODULE_PAYMENT_CHECKBOOK_API_SECRET;
      $this->recipient_name = MODULE_PAYMENT_CHECKBOOK_RECIPIENT_NAME;
      $this->recipient_email = MODULE_PAYMENT_CHECKBOOK_RECIPIENT_EMAIL;
      $this->redirect_URL = HTTPS_SERVER . DIR_WS_CATALOG . 'ext/modules/payment/checkbook/callback.php';
      if ((int)MODULE_PAYMENT_CHECKBOOK_CC_ORDER_STATUS_ID > 0) {
        $this->order_status = MODULE_PAYMENT_CHECKBOOK_CC_ORDER_STATUS_ID;
      }
      //$_SESSION['clientID'] = MODULE_PAYMENT_CHECKBOOK_CLIENT_ID;
	//$_SESSION['apiSecret'] = MODULE_PAYMENT_CHECKBOOK_API_SECRET;
	//$_SESSION['recName'] = MODULE_PAYMENT_CHECKBOOK_RECIPIENT_NAME;
	//$_SESSION['recEmail'] = MODULE_PAYMENT_CHECKBOOK_RECIPIENT_EMAIL;
	//$_SESSION['redirectURL'] = $this->redirect_URL;
     // $this->gateway_addresses = array('212.227.34.218', '212.227.34.219', '212.227.34.220');

      if (is_object($order)) $this->update_status();
	$this->sandboxMode = ((MODULE_PAYMENT_CHECKBOOK_SANDBOX == 'True') ? true : false);
	if ($this->sandboxMode){
		$this->baseURL = "https://sandbox.checkbook.io";
	}else{
		$this->baseURL = "https://checkbook.io";
	}


    }

// class methods
    function update_status() {
      global $order;
      if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_CHECKBOOK_CC_ZONE > 0) ) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_CHECKBOOK_CC_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
        while ($check = tep_db_fetch_array($check_query)) {
          if ($check['zone_id'] < 1) {
            $check_flag = true;
            break;
          } elseif ($check['zone_id'] == $order->billing['zone_id']) {
            $check_flag = true;
            break;
          }
        }

        if ($check_flag == false) {
          $this->enabled = false;
        }
      }
    }

    function javascript_validation() {
      return false;
    }

    function selection() {
      return array('id' => $this->code,
                   'module' => $this->public_title);
    }

    function pre_confirmation_check() {
      return false;
    }

    function confirmation() {
      
	 global $order;
	session_start();
	$authorization_code = $_GET['code'];
	$oauth_url = $this->baseURL . "/oauth/authorize?client_id=" . $this->client_id . '&response_type=code&state=asdfasdfasd &scope=check&redirect_uri=' . $this->redirect_URL;

	$_SESSION['oauth_url'] = $oauth_url;
	$pay_with_checkbook_button_html = '<div id="checkbook_button_area"><span class=""><a id="checkbook_payment" href="javascript:openOauthModal()" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only ui-priority-secondary" role="button" aria-disabled="false"><span class="ui-button-text">Authenticate with Checkbook</span></a></span></div>';
      $confirmation = array('fields' => array(array('title' => 'Complete Transaction with Checkbook',
                                                    'field' => $pay_with_checkbook_button_html)));
 
	?>
	<style>
	#authIframe{
		width:100%;
		height:calc(87vh);
	}
        .tingle-modal-box__content {
  				padding: 0.5rem 0.5rem !important;
				}
        iframe{
        	margin-bottom:0px;
        }
        .tingle-modal__close {
        	font-size:4rem !important;
        }
				.tingle-modal-box {
        width:40% !important;
        height:90% !important;
				}
			</style>
	<link rel="stylesheet" type="text/css" href=<?php echo '"' . tep_href_link('ext/modules/payment/checkbook/tingle.css') . '"'  ?>>
	<script src=<?php echo '"' . tep_href_link('ext/modules/payment/checkbook/tingle.js') . '"'  ?>></script>
	<script>
	var modal = new tingle.modal({
					    footer: false,
					    stickyFooter: false,
					    closeMethods: ['overlay', 'button', 'escape'],
					    closeLabel: "Close",
					    cssClass: ['custom-class-1', 'custom-class-2'],
					    onOpen: function() {
					        console.log('modal open');
					    },
					    onClose: function() {
					        console.log('modal closed');
					    },
					    beforeClose: function() {
					        // here's goes some logic
					        // e.g. save content before closing the modal
					        return true; // close the modal
					        return false; // nothing happens
					    }
					});

			modal.setContent('<iframe id = "authIframe" src=<?php echo '"' . $oauth_url . '"' ?>  scrolling="yes" ></iframe>');

		function openOauthModal(){
			modal.open();		
		}
		//Next steps: create a local folder where all the data can be stored (Reference the existing plugins for where data is being stored) Generate the folder and use the same flow from the Wordpress plugin pre Wordpress_gets. Move tingle files so that a modal opens up on the page and then the rest can likely be taken from the COD plugin rather than this one. Or reference this one either way is fine. 
// 1) establish directory for the data
// 2) move the tingle data here 
// 3) implement OAUTh flow may have to use the callback but should be pretty easy because of exisitng code
// 4) compelte payment flow using any OSCommerce specific stuff
	</script>


 <?php


	if($_SESSION['authorized'] == "true")
				{ 
				?>
					<script>
						document.getElementById('checkbook_button_area').innerHTML = '<p style="color:green;"> Authorization complete. You are now ready to make a payment via Checkbook. </p>';

						</script>
				<?php
				 
				}
				else
				{
					?>
						<script>
						document.getElementById('checkbook_button_area').innerHTML = '<span class=""><a id="checkbook_payment" href="javascript:openOauthModal()" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only ui-priority-secondary" role="button" aria-disabled="false"><span class="ui-button-text">Authenticate with Checkbook</span></a></span>';

						</script>
	
					<?php
				}			
     return $confirmation;
    }

    function process_button() {
      return false;
	 }

    function before_process() {

	if(!$_SESSION['authorized'] == true)
		tep_redirect(tep_href_link(FILENAME_CHECKOUT_CONFIRMATION, 'error_message=' . urlencode('Please click "Authenticate with Checkbook" to complete the transaction.'), 'NONSSL', true, false));

global $order;
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => "https://sandbox.checkbook.io/v3/check/digital",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => "{\"name\":\"". $this->recipient_name  ."\",\"recipient\":\"". $this->recipient_email  . "\",\"amount\":" . $order->info['total'] . "}",
  CURLOPT_HTTPHEADER => array(
    "Authorization: Bearer " . $_SESSION['bearerToken'],
    "Cache-Control: no-cache",
    "Content-Type: application/json"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);
if ($err)
			{
			  error_log("cURL Error #:" . $err);
			} else
			{
				 //Now that the post is complete...
			   error_log($response);
         if(!array_key_exists('id', json_decode($response, true)))
	 {
			tep_redirect(tep_href_link(FILENAME_CHECKOUT_CONFIRMATION, 'error_message=' . urlencode(json_decode($response, true)['error']), 'NONSSL', true, false));
			 	
         }else{
	
}
}

//throw new Exception($response);

         return false;
    }


    function after_process() {
      return false;
    }

    function get_error() {
     return false;
    }

    function check() {
      if (!isset($this->_check)) {
        $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_CHECKBOOK_STATUS'");
        $this->_check = tep_db_num_rows($check_query);
      }
      return $this->_check;
    }

    function install() {
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Checkbook', 'MODULE_PAYMENT_CHECKBOOK_STATUS', 'False', 'Do you want to accept Checkbook.io payments?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Client ID', 'MODULE_PAYMENT_CHECKBOOK_CLIENT_ID', '123412341324', 'Your Client ID from the Checkbook Dashboard', '6', '2', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('API Secret', 'MODULE_PAYMENT_CHECKBOOK_API_SECRET', '123412341234', 'Your API Secret key from the Checkbook Dashboard', '6', '3', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Check Recipient (your name)', 'MODULE_PAYMENT_CHECKBOOK_RECIPIENT_NAME', '', 'The name of the person or business to which checks should be made out.', '6', '4', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Recipient Email Address', 'MODULE_PAYMENT_CHECKBOOK_RECIPIENT_EMAIL', '', 'The email address to which checks should be sent.', '6', '4', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Redirect/Callback URL', 'MODULE_PAYMENT_CHECKBOOK_REDIRECT_URL', '" . HTTPS_SERVER . DIR_WS_CATALOG . "ext/modules/payment/checkbook/callback.php', 'This value should not be changed. Updated the Callback URL value in your Checkbook API Dashboard with this value.', '6', '4', now())");
 	tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Sandbox Mode', 'MODULE_PAYMENT_CHECKBOOK_SANDBOX', 'True', 'Check to operate in Sandbox mode, uncheck to go Live.', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
     
}

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_PAYMENT_CHECKBOOK_STATUS', 'MODULE_PAYMENT_CHECKBOOK_CLIENT_ID', 'MODULE_PAYMENT_CHECKBOOK_API_SECRET', 'MODULE_PAYMENT_CHECKBOOK_RECIPIENT_NAME', 'MODULE_PAYMENT_CHECKBOOK_RECIPIENT_EMAIL', 'MODULE_PAYMENT_CHECKBOOK_REDIRECT_URL', 'MODULE_PAYMENT_CHECKBOOK_SANDBOX');
    }
  }
?>
