<?php
/**
* eNom Pro WHMCS Add-on Hooks
* @version @VERSION@
* Copyright 2012 Orion IP Ventures, LLC.
* Licenses Resold by Circle Tree, LLC. Under Reseller Licensing Agreement
*/
function enom_pro_admin_balance ($vars)
{
    if (!class_exists('enom_pro'))
        require_once 'enom_pro.php';
    if (isset($_REQUEST['enom_pro_get_enom_balance'])) {
        try {
            $enom = new enom_pro();
            $warning_level = $enom->get_addon_setting('balance_warning');
            $available = (float) preg_replace("/([^0-9.])/i", "", $enom->getAvailableBalance());
            $warning = $available <= $warning_level ? true : false;
            if ('off' == strtolower($warning_level))
                $warning = false;
            $class = $warning ? 'alert-danger' : 'alert-success';
						$str = '';
            $str .= '<div id="enom_balance_message" class="alert enom_pro_widget '.$class.'">';
            $str .= '&nbsp;Enom Credit Balance: '.$enom->getBalance()." Available: <b>".$enom->getAvailableBalance().'</b>
            <a class="btn btn-default btn-xs" href="https://www.enom.com/myaccount/RefillAccount.asp" target="_blank">Refill Account</a>';
            $str .= "</div>";
            if ($warning) {
                $str .= '
<script>
jQuery(function($) {
    var $message = $("#enom_balance_message");
    setInterval(function  () {
        if ($message.hasClass("inset")) {
              $message.removeClass("inset");
        } else {
              $message.addClass("inset");
        }
    },500)
});
</script>
';
            }
            $content = $str;
            if (
                $enom->license->is_update_available() && 
                ! (basename($_SERVER['SCRIPT_NAME']) == 'addonmodules.php')
            ) {
                $content .= '<div class="alert alert-warning aligncenter">Update available: ';
                $content .=     $enom->license->get_latest_version() . '<br/>';
                $content .= '<a class="btn btn-default" href="'.enom_pro_license::DO_UPGRADE_URL.'">Upgrade automatically</a>';
                $content .= '</div>';
            }
        } catch (Exception $e) {
            $content = $e->getMessage();
        }
        echo enom_pro::minify($content);
        exit;
    }
    $content = '<div id="enombalance"><span class="enom_pro_loader"></span></div>';
    $jquerycode = '
    var $refresh_form = jQuery("#refreshEnomBalance");
    $refresh_form.live("submit", function  () {
        var $elem = jQuery("#enombalance");
        $elem.html(\'<span class="enom_pro_loader"></span>\');
        jQuery.post("index.php", $(this).serialize(),
                function(data){
                  $elem.html(data);
            });

            return false;
        });
        if ($refresh_form.is(":visible")) {
            $refresh_form.trigger("submit");
        }';

    return array(
            'title'=>'<a href="'.enom_pro::MODULE_LINK.'">@NAME@</a>' . 
                ' - Reseller Balance <img src="images/icons/transactions.png" align="absmiddle" height="16px" width="16px" border="0">' . 
                get_enom_pro_widget_form('enom_pro_get_enom_balance', 'refreshEnomBalance'),
            'content'=>$content,
            'jquerycode'=>enom_pro::minify($jquerycode),
        );
}
add_hook("AdminHomeWidgets",1,"enom_pro_admin_balance");

function enom_pro_admin_ssl_certs ($vars)
{
	if ( !class_exists( 'enom_pro' ) ) {
		require_once 'enom_pro.php';
	}
    if (isset($_REQUEST['enom_pro_get_ssl_certs'])) {
        try {
        $enom = new enom_pro();
            $expiring_certs = $enom->getExpiringCerts();
						$str = '';
            $str .= '<div class="enom_pro_widget alert '.(count($expiring_certs) > 0 ? 'alert-danger' : 'alert-success').'">';
            if (count($expiring_certs) > 0 ) {
                $str .= ' <table class="table table-condensed table-hover">
                <tr>
									<th>Domain</th>
									<th>Status</th>
									<th>Product</th>
									<th>Expiration Date</th>
									<th>Hide</th>
                </tr>
                ';
                foreach ($expiring_certs as $cert) {
                    $str .= '<tr>
                    <td> ';
                    if (count($cert['domain']) > 0)
                        $str .= rtrim(implode(', ', array_values($cert['domain'])), ', ');
                    else
                        $str .= 'Not Issued';
                    $str .='</td>
                    <td style="text-align:center;"><a href="http://www.enom.com/secure/configure-ssl-certificate.aspx?certid='.$cert['CertID'].'" target="_blank" class="btn btn-default" >'.$cert['status'].'</a></td>
                    <td style="text-align:center;">'.$cert['desc'].'</td>
                    <td style="text-align:center;">'.$cert['expiration_date'].'</td>
                    ';
									$str .= '<td><a href="index.php?action=enom_pro_hide_ssl&certid='.$cert['CertID'].'">[x]</a></td>';
                }
            } elseif (! isset($_REQUEST['show_all']) && "" != ($hidden = enom_pro::get_addon_setting('ssl_hidden'))) {
							//May be hidden
							$count = count($hidden);
							$str .= '<p>No Certificates Expiring in the next '.$enom->get_addon_setting('ssl_days').' days. </p>';
							$str .= "<p><b>But there are $count hidden certificates</b>" . ' <a href="#" class="show_hidden_ssl">Show All</a></p>';
						} else {
                //No expiring certs
                $str .= '<p>Phew! No Certificates Expiring in the next '.$enom->get_addon_setting('ssl_days').' days.</p>';
            }
            $str .= "</div>";
            $content = $str;
        } catch (Exception $e) {
            $content = $e->getMessage();
        }
        echo enom_pro::minify($content);
        exit;
    }
    $content = '<div id="enomSSL"><span class="enom_pro_loader"></span></div>';
    $jquerycode = '
    var $refresh_ssl = jQuery("#refreshEnomSSL");
    $refresh_ssl.on("submit", function  () {
    	var $elem = jQuery("#enomSSL");
    	$elem.html(\'<span class="enom_pro_loader"></span>\');
        jQuery.post("index.php", $(this).serialize(),
            function(data){
                $elem.html(data);
        });
        return false;
    });
		if($refresh_ssl.is(":visible")) {
				$refresh_ssl.trigger("submit");
		}
		$("#enomSSL").on("click", ".show_hidden_ssl", function  (){
			$refresh_ssl.append("<input type=\"hidden\" name=\"show_all\" value=\"true\" />");
			$refresh_ssl.trigger("submit");
			return false;
		});
';
    return array(
            'title'=>'<a href="'.enom_pro::MODULE_LINK.'">@NAME@</a> - SSL Certificates '.
                    '<img src="images/icons/securityquestions.png" align="absmiddle" height="16px" width="16px" border="0">' . 
                    get_enom_pro_widget_form('enom_pro_get_ssl_certs', 'refreshEnomSSL'),
            'content'=>$content,
            'jquerycode'=>enom_pro::minify($jquerycode),
        );
}

add_hook("AdminHomeWidgets",1,"enom_pro_admin_ssl_certs");

function enom_pro_admin_expiring_domains ($vars)
{
    if (!class_exists('enom_pro'))
        require_once 'enom_pro.php';
    if (isset($_REQUEST['enom_pro_check_expiring_domains'])) {
        $enom = new enom_pro();
        try {
            $stats = $enom->getAccountStats();
						$str = '';
            $str .= '<div class="enom_pro_widget">';
            $str .= '<table class="table-hover" ><tbody>
                    <tr>
                        <td class="enom_stat_button">
                            <a class="btn btn-success '.($stats['registered'] > 0  ? '' : 'disabled' ).'"
                                data-tab="registered" 
                                href="'.enom_pro::MODULE_LINK.'&action=get_domains" title="View Domains">
                                '.$stats['registered'].'
                            </a>
                            <div class="enom_pro_loader small hidden"></div>
                        </td>
                        <td class="enom_stat_label">Registered Domains</td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div id="enom_pro_registered"></div>                
                        </td>                    
                    </tr>
                    <tr>
                        <td class="enom_stat_button">
                            <a class="btn btn-warning '.($stats['expiring'] > 0  ? '' : 'disabled' ).'"
                                data-tab="expiring" 
                                href="'.enom_pro::MODULE_LINK.'&action=get_domains&tab=expiring">
                                    '.$stats['expiring'].'
                            </a>
                            <div class="enom_pro_loader small hidden"></div>
                        </td>
                        <td class="enom_stat_label">Expiring Domains</td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div id="enom_pro_expiring"></div>                
                        </td>                    
                    </tr>
                    <tr>
                        <td class="enom_stat_button">
                            <a class="btn btn-danger '.($stats['expired'] > 0  ? '' : 'disabled' ).'"
                                data-tab="expired" 
                                href="'.enom_pro::MODULE_LINK.'&action=get_domains&tab=expired">
                                    '.$stats['expired'].'
                            </a>
                            <div class="enom_pro_loader small hidden"></div>
                        </td>
                        <td class="enom_stat_label">Expired Domains</td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div id="enom_pro_expired"></div>                
                        </td>                    
                    </tr>
                    <tr>
                        <td class="enom_stat_button">
                            <a class="btn btn-info '.($stats['redemption'] > 0  ? '' : 'disabled' ).'" 
                                data-tab="redemption" 
                                href="'.enom_pro::MODULE_LINK.'&action=get_domains&tab=redemption">
                                    '.$stats['redemption'].'
                            </a>
                            <div class="enom_pro_loader small hidden"></div>
                        </td>
                        <td class="enom_stat_label">Redemption Period</td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div id="enom_pro_redemption"></div>                
                        </td>                    
                    </tr>
                    <tr>
                        <td class="enom_stat_button">
                            <a class="btn btn-inverse '.($stats['ext_redemption'] > 0  ? '' : 'disabled' ).'"
                                    href="http://www.enom.com/domains/Domain-Manager.aspx?tab=redemption"
                                    target="_blank" >
                                        '.$stats['ext_redemption'].'
                            </a>
                            <div class="enom_pro_loader small hidden"></div>
                        </td>
                        <td class="enom_stat_label">Extended Redemption Period</td>
                    </tr>
            </tbody></table>';
            $content = $str;
        } catch (Exception $e) {
            $content = $e->getMessage();
        }
        echo enom_pro::minify($content);
        exit;
    }
    $content = '<div id="enomExpiring"><span class="enom_pro_loader"></span></div>';
    $jquerycode = '
            var $refresh_expiring = jQuery("#refreshExpiring");
    $refresh_expiring.on("submit", function  () {
        var $elem = jQuery("#enomExpiring");
        $elem.html(\'<span class="enom_pro_loader"></span>\');
        jQuery.post("index.php", $(this).serialize(),
            function(data){
            $elem.html(data);
        });
        return false;
    });
    if($refresh_expiring.is(":visible")) {
        $refresh_expiring.trigger("submit");
    }';

    return array(
            'title'	=>	'<a href="'.enom_pro::MODULE_LINK.'">@NAME@</a>' . 
                ' - Domain Stats <img src="images/icons/domains.png"' . 
                ' align="absmiddle" height="16px" width="16px" border="0">' . 
                get_enom_pro_widget_form('enom_pro_check_expiring_domains', 'refreshExpiring'),
            'content'=>$content,
            'jquerycode'=>enom_pro::minify($jquerycode),
        );
}
add_hook("AdminHomeWidgets",1,"enom_pro_admin_expiring_domains");

function enom_pro_admin_transfers ($vars)
{
    if (!class_exists('enom_pro'))
        require_once 'enom_pro.php';
        if (isset($_REQUEST['enom_pro_check_transfers'])) {
            $enom = new enom_pro();
            try {
                $transfers = $enom->getTransfers();
								$str = '';
                if (empty($transfers)) {
                    $str .= '<div class="alert alert-success enom_pro_widget">No pending transfers found in WHMCS</div>';
                    $str .= '</div>';
                    echo $str;
                    die;
                }
                $str .= '<div class="enomtransfers enom_pro_widget">';
                $str .= ' <table id="enom_pro_transfers_table">';
                $str .= '
                <tr>
                    <th>Domain</th>
                    <th>WHMCS Domains</th>
                    <th>Orders</th>
                </tr>
                ';
                foreach ($transfers as $domain) {
                    //Loop through the actual domains returned from WHMCS
                    $edit_domain_button = '<a href="clientsdomains.php?userid='.$domain['userid'].'&id='.$domain['id'].'" class="btn btn-default" >Edit</a>';
                    $str .= '<tr>
                    <td>
                        <a class="domain_name" target="_blank" title="View WHOIS" href="http://www.whois.net/whois/'.$domain['domain'].'">'.$domain['domain'].'
                    </td>
                    <td style="text-align:center;">
                            '.$edit_domain_button.'
                    </td>
                    <td>
                        ';
                    if (count($domain['statuses']) > 0):
                    $str .= '
                        <table class="none">
                        <tr>
                            <th>eNom Order ID</td>
                            <th>Actions</td>
                            <th class="center">Description</td>
                        </tr>
                    ';
                            //now we need to loop through the multiple statuses returned for each domain by the enom API
                            foreach ($domain['statuses'] as $status) {
                                $status = (array) $status;
                                switch ($status['statusid']) {
                                    case 22:
                                        //Cancelled, domain is locked or not yet 60 days old
                                        $action = ' <form method="GET" class="resubmit_enom_transfer ajax_submit" action="'.$_SERVER['PHP_SELF'].'">
                                                        <input type="hidden" name="action"  value="resubmit_enom_transfer_order"/>
                                                        <input type="hidden" name="orderid"  value="'.$status['orderid'].'"/>
                                                        <input type="image" src="images/icons/import.png "class="button" title="Re-Submit Transfer Order"/>
                                                    </form>';
                                        break;
                                    case 9:
                                    case 11:
                                        //Awaiting auto-verification of transfer request
                                        $action = ' <form method="GET" class="resend_enom_activation ajax_submit" action="'.$_SERVER['PHP_SELF'].'">
                                                        <input type="hidden" name="action"  value="resend_enom_transfer_email"/>
                                                        <input type="hidden" name="domain"  value="'.$domain['domain'].'"/>
                                                        <input type="image" src="images/icons/resendemail.png "class="button" title="Re-Send Transfer Authorization E-Mail"/>
                                                    </form>';
                                    break;
                                    default:
                                        $action = false;
                                }
                                $str .= "
                            <tr>
                                <td><a target=\"_blank\" title=\"Order Date: {$status['orderdate']}\" href=\"https://www.enom.com/domains/TransferStatus.asp?transferorderid={$status['orderid']}\">{$status['orderid']}</a></td>
                                <td style=\"text-align:center;\" >".($action ? $action : '<input type="image" src="images/icons/disabled.png" class="btn btn-default" title="No actions for this order status"/>')."</td>
                                <td>{$status['statusdesc']}</td>
                            </tr>
                                ";
                            }

                    $str.="
                        </table>";
                    else:
                        $str .=	'<div class="alert alert-info">No Orders Found '.$edit_domain_button.'</div>';
                    endif;
                    $str .= "
                    </td>
                </tr>";
                }
                $str .= "</table></div>";
                $content = $str;
            } catch (Exception $e) {
                $content = $e->getMessage();
            }
            echo enom_pro::minify($content);
            exit;
        }
        $content = '<div id="enomtransfers"><span class="enom_pro_loader"></span></div>';

        //Yes, $.ready is redundant, but since WHMCS doesnt alias $, we use it here for convenience;
        $jquerycode = '
        jQuery(document).ready(function($){
                var $refresh_transfers = $("#refreshEnomTransfers");
        $refresh_transfers.live("submit", function  () {
        var $elem = $("#enomtransfers");
        $elem.html(\'<span class="enom_pro_loader"></span>\');
            $.post("index.php", $(this).serialize(),
                function(data){
                  $elem.html(data);
                });

                return false;
        });
        if ($refresh_transfers.is(":visible"))
                $refresh_transfers.trigger("submit");

        $(".ajax_submit").live("submit", function  () {
            var $this = $(this),
                $submit = $this.find("input[type=submit]");
            $(".activation_loading", $this).remove();
            $submit.attr("disabled","disabled");
            $this.append("<div class=\"activation_loading\"><span class=\"enom_pro_loader\"></span></div>");
            $.ajax({
                data: $this.serialize(),
                success: function  (response) {
                    $(".activation_loading", $this).html(response);
                    $submit.removeAttr("disabled");
                }
            });

        return false;
        });
    });';

        return array(
                'title'	=>	'<a href="'.enom_pro::MODULE_LINK.'">@NAME@</a> ' . 
                    '- Pending Transfers <img src="images/icons/clientlogin.png" align="absmiddle" height="16px" width="16px" border="0">' . 
                    get_enom_pro_widget_form('enom_pro_check_transfers', 'refreshEnomTransfers'),
                'content'=>$content,
                'jquerycode'=>enom_pro::minify($jquerycode),
            );
}
add_hook("AdminHomeWidgets",1,"enom_pro_admin_transfers");

function get_enom_pro_widget_form ($action, $id)
{
    if ('configadminroles.php' == basename($_SERVER['PHP_SELF']))
        return '';
    ob_start();?>
    <form id="<?php echo $id; ?>" class="refreshbutton" action="<?php echo $_SERVER['PHP_SELF'];?>">
        <input type="hidden" name="<?php echo $action; ?>" value="1" />
        <input type="submit" value="Refresh" class="btn btn-default" />
    </form>
    <?php
    $return = ob_get_contents();
    ob_end_clean();

    return $return;
}

/**
 * Admin Page Action API Hooks
 */
add_hook("AdminAreaHeadOutput", -89512, "enom_pro_admin_css");
function enom_pro_admin_css ()
{
    //	Only load on applicable pages
    $pages = array('index.php', 'addonmodules.php');
    if (in_array(basename($_SERVER['SCRIPT_NAME']), $pages) || ( isset($_GET['module']) && 'enom_pro' == $_GET['module']) ) { ob_start(); ?>
			<link rel="stylesheet" href="../modules/addons/enom_pro/css/admin.min.css" />
			<link rel="stylesheet" href="../modules/addons/enom_pro/css/bootstrap.min.css" />
			<link rel="stylesheet" href="../modules/addons/enom_pro/css/bootstrap-theme.min.css" />
			<script src="<?php echo enom_pro::MODULE_LINK ?>&action=getAdminJS"></script>
<?php

			$return = ob_get_contents();
			ob_end_clean();
			return $return;
    } else {
        return '';
    }
}
function enom_pro_admin_actions ()
{
    $enom_actions = array(
        'resend_enom_transfer_email',
        'resubmit_enom_transfer_order',
        'add_enom_pro_domain_order',
        'set_results_per_page',      
        'render_import_table',
        'get_domain_whois',
        'clear_cache',
        'clear_price_cache',
        'get_domains',
        'do_upgrade',
        'do_upgrade_check',
        'get_pricing_data',
        'save_domain_pricing',
        'dismiss_manual_upgrade',
        'install_ssl_template',
				'enom_pro_hide_ssl',
				'sort_domains',
				'dismiss_alert',
				'getAdminJS'
    );
    //Only load this hook if an ajax request is being run
    if (! (isset($_REQUEST['action']) && in_array($_REQUEST['action'], $enom_actions))) {
        return;
    }
    //Include our class if needed
    if (!class_exists('enom_pro'))
        require_once 'enom_pro.php';
    try {
        $controller = new enom_pro_controller();
        $controller->route();
    } catch (Exception $e) {
        header("HTTP/1.0 400 Bad Request", true);
        echo $e->getMessage();
    }
    die(); 
}
add_hook("AdminAreaPage",-284917,"enom_pro_admin_actions");
/**
 * Makes the namespinner markup
 */
function enom_pro_namespinner ()
{
    if (!class_exists('enom_pro'))
        require_once 'enom_pro.php';
    global $_LANG;
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'spinner') {
        $enom = new enom_pro();
        //Only return the API results if this a RESTful request from the AJAX widget
        $parts = $enom->getDomainParts($_REQUEST['domain']);
        //eNom pops the SLD up to TLD in the parsed response if there is no .com
        $sld_posing_as_tld = (bool) $parts['SLD']; //So we check if the SLD result is populated
        if (!$sld_posing_as_tld) {
            $sld = $parts['TLD'];
            $tld = "com";//This doesn't matter to the enom API, as we're just looking for name spins
        } else {
            $sld = $parts['SLD'];
            $tld = $parts['TLD'];
        }
        $domain_name = $sld.'.'.$tld;
        $results = $enom->getSpinner($domain_name);
        if (count($results['domains']) > 0 ) {
            echo ' <h3>'.$_LANG['cartotherdomainsuggestions'].'</h3>';
            echo '<div class="spinner_results_wrapper col_'.(int) $enom->get_addon_setting('spinner_columns').'">';
            foreach ($results['domains'] as $domain) {
                if (isset($results['pricing'][$domain['tld']])) {
                //Only return spin results if we have pricing defined in WHMCS
                    $id = str_replace(array(".","-"), "_", $domain['domain']);
                    echo ' <div class="spin_result">';
                            echo '<input type="checkbox" id="'.$id.'" name="domains[]" value="'.$domain['domain'].'" />';
                            echo '<label class="btn btn-default" for="'.$id.'">'.$domain['domain'];
                            echo '</label>';
                                echo '<select name="domainsregperiod['.$domain['domain'].']" >';
                                    foreach ($results['pricing'][$domain['tld']] as $year=>$price) {
                                        if ((int) $price > 0 && 'id' !== $year) {
                                            echo '<option value="'.$year.'">'.$year.' '.$_LANG['orderyears'].' @ '.$price.'</option>';
                                        }
                                    }
                            echo '</select>';
                    echo '</div>';
                } else {
                    if ($enom->debug()) echo 'This TLD doesn\'t have a price defined:'.$domain['tld'].' <br/>';
                }
            }

            if ($enom->get_addon_setting('spinner_checkout') == "on") {
                //Only show the add to cart button if enabled

                $css_class = enom_pro::get_addon_setting('cart_css_class');
                if (enom_pro::get_addon_setting('custom_cart_css_class') !== "")
                    $css_class = enom_pro::get_addon_setting('custom_cart_css_class');
                if (is_null($css_class))
                    $css_class = 'btn btn-primary';
                echo '<input class="'.$css_class.'" type="submit" value="'.$_LANG['addtocart'].'" />';
            }
            echo '</div>';
        } else {
            if ($enom->debug()) echo 'No results';
        }
        die();
    }
    $spinnercode = '';
    if (enom_pro::get_addon_setting("spinner_css") == "on") {
        //Only include the css if enabled
        $spinnercode .= '<link rel="stylesheet" href="modules/addons/enom_pro/spinner_style.css" />';
    }
    switch (enom_pro::get_addon_setting("spinner_animation")) {
			case "Slow":
				$animation = '.slideDown(750);';
			break;

			case "Medium":
				$animation = '.slideDown(400);';
			break;

			case "Fast":
				$animation = '.slideDown(200);';
			break;

			case "Off":
			default:
				$animation = '.show();';
			break;
    }
    $spinnercode .= '
    <div id="spinner_ajax_results" style="display:none"></div>
    <script>';
    if (enom_pro::debug()) {
        //Make sure jQuery is loaded when debugging
        $spinnercode .= '
            if (typeof(jQuery) == "undefined") alert("eNom Pro Debug\n\njQuery is not loaded. Make sure your template includes jquery javascript library in header.tpl. See jquery.org for more info.");
            ';
    }
    if (isset($_REQUEST['sld']) && count($_REQUEST['sld']) > 1) {
        //Check for the cart SLD array
        $domain = $_REQUEST['sld'][0].'.'.ltrim($_REQUEST['tld'][0],'.');
    } elseif (isset($_REQUEST['sld'])) {
        $domain = $_REQUEST['sld'].'.'.ltrim($_REQUEST['tld'],'.');
        //Get the first array domain item, the registration one
    } else {
        $domain = $_REQUEST['domain'];
    }
    $domain = addslashes($domain);
    $spinnercode .= '
    jQuery(function($) {
        $.post("'.$_SERVER['PHP_SELF'].'", {action:"spinner", domain:"'.$domain.'", token: "'.$GLOBALS['smarty']->_tpl_vars['token'].'" }, function  (data) {
            $("#spinner_ajax_results").html(data)'.$animation.'
        });
        $("#spinner_ajax_results").on("click", "INPUT", function  () {
            var $elem = $(this);
            if ($elem.is(":checked")) {
                $elem.parent("div").addClass("checked")
            } else {
                $elem.parent("div").removeClass("checked")
            }
        })
    });';
    $spinnercode .= '</script>';

    return array('namespinner' => $spinnercode);
}


add_hook("ClientAreaPage",-10101,"enom_pro_namespinner");

function enom_pro_clientarea_transfers ($vars)
{
    global $enom_pro_transfers;
    if (! class_exists('enom_pro') )
        require_once 'enom_pro.php';
    //Prep the userid of currently logged in account
    $uid = isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : 0; //Set this to 0 for security to return no results if the WHMCS uid is not set in the session
    //This is where the magic happens
    //Only do the API request asynchronously if there are transfers
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'domains' && $_REQUEST['refresh'] == 'true') {
        $enom = new enom_pro();
        //Set cache control headers so IE doesn't cache the response (causing support tickets when a transfer has been approved, for instance)
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        //Set the headers so jQuery parses the response as well formed JSON
        header("Content-type: application/json");
        //send a JSON response to the client
        echo json_encode($enom->getTransfers($uid));
        //Exit, we don't need to send WHMCS ;-)
        die();
    } else {
        //Prepare the query to check if the current user has any pending enom transfers
        $query = "SELECT `userid`,`type`,`domain`,`status` 
                    FROM `tbldomains` 
                    WHERE `registrar`='enom' 
                    AND `status`='Pending Transfer' 
                    AND `userid`=" . $uid;
        $result = mysql_query($query);
        //Check if there are any results
				$there_are_results = $result && (mysql_num_rows($result) > 0) ? true : false;
        if ($there_are_results) {
            $enom_pro_transfers = true;
        } else {
            $enom_pro_transfers = false;
        }

        return array('enom_transfers' => $there_are_results);
    }
}
add_hook("ClientAreaPage",2,"enom_pro_clientarea_transfers");
function enom_pro_srv_page ($vars)
{
    if (! ('clientarea.php' == basename($_SERVER['SCRIPT_NAME']) && isset($_GET['action']) && 'domaindetails' == $_GET['action']))
        return $vars;
    if (! (isset( $vars['registrar']) && 'enom' == $vars['registrar']) )
        return $vars;
    //We only get here if there is an active enom domain on the domain details.tpl page
    $vars['enom_srv'] = true;

    return $vars;
}
add_hook("ClientAreaPage",-30101,"enom_pro_srv_page");
add_hook("DailyCronJob", 1, "enom_pro_cron");
function enom_pro_cron() {
	$salt = 'lJsif3n1F9GKeStIdM9VAeJrrPC1grpBpSZLtWMb';
	require_once 'enom_pro.php';
	$enom = new enom_pro();
	$lock = $enom->get_addon_setting( 'cron_lock' );
	if ( empty( $lock ) || false === $lock || $lock !== md5( strrev( $salt ) . date( 'Ymd' ) . $salt ) ) {
			echo ENOM_PRO . ': Begin CRON' . PHP_EOL;
			enom_pro::log_activity( ENOM_PRO . ': Begin CRON Job' );
			$count = $enom->send_all_ssl_reminder_emails();
			echo ENOM_PRO . ': Sent ' . $count . ' SSL Reminder Email(s)' . PHP_EOL;
			enom_pro::log_activity( ENOM_PRO . ': End CRON Job. Sent ' . $count . ' SSL Reminder Email(s)' );
			echo ENOM_PRO . ': END CRON' . PHP_EOL;
			$new_lock = md5( strrev( $salt ) . date( 'Ymd' ) . $salt );
			$enom->set_addon_setting( 'cron_lock', $new_lock );
		} else {
		$msg = ENOM_PRO . ': Cron Already Ran Once Today';
		echo $msg . PHP_EOL;
		enom_pro::log_activity( $msg );
	}
}