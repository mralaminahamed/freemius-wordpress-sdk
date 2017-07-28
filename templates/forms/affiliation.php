<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       1.0.3
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * @var array $VARS
	 */
	$slug = $VARS['slug'];

	/**
	 * @var Freemius $fs
	 */
	$fs = freemius( $slug );

	$user            = $fs->get_user();
	$affiliate       = $fs->get_affiliate();
	$affiliate_terms = $fs->get_affiliate_terms();

	$plugin_title = $fs->get_plugin_title();
	$module_type  = $fs->is_plugin() ?
        WP_FS__MODULE_TYPE_PLUGIN :
        WP_FS__MODULE_TYPE_THEME ;

	$commission = $affiliate_terms->get_formatted_commission();

    $readonly                      = false;
    $is_affiliate                  = false;
    $is_pending_affiliate          = false;
    $email_address                 = ( is_object( $user ) ?
        $user->email :
        '' );
    $full_name                     = ( is_object( $user ) ?
        $user->get_name() :
        '' );
    $paypal_email_address          = '';
    $domain                        = '';
    $extra_domains                 = array();
    $promotion_method_social_media = false;
    $promotion_method_mobile_apps  = false;
    $statistics_information        = false;
    $promotion_method_description  = false;
    $members_dashboard_login_url   = 'https://members.freemius.com/login/';

    $affiliate_application_data = $fs->get_affiliate_application_data();

    if ( is_object( $affiliate ) ) {
        $is_affiliate = true;

        if ( $affiliate->is_pending() ) {
            $readonly             = 'readonly';
            $is_pending_affiliate = true;

            $paypal_email_address         = $affiliate->paypal_email;
            $domain                       = $affiliate->domain;
            $statistics_information       = $affiliate_application_data['stats_description'];
            $promotion_method_description = $affiliate_application_data['promotion_method_description'];

            if ( ! $affiliate_terms->is_any_site_allowed ) {
                $domain = $affiliate->domain;

                if ( ! empty( $affiliate_application_data['additional_domains'] ) ) {
                    $extra_domains = $affiliate_application_data['additional_domains'];
                }
            }

            if ( ! empty( $affiliate_application_data['promotion_methods'] ) ) {
                $promotion_methods             = explode( ',', $affiliate_application_data['promotion_methods'] );
                $promotion_method_social_media = in_array( 'social_media', $promotion_methods );
                $promotion_method_mobile_apps  = in_array( 'mobile_apps', $promotion_methods );
            }
        }
    }

    $affiliate_tracking = 30;

    if ( is_object( $affiliate_terms ) ) {
        $affiliate_tracking = ( ! is_null( $affiliate_terms->cookie_days ) ?
            ( $affiliate_terms->cookie_days . '-day' ) :
            fs_text( 'non-expiring', $slug ) );
    }
?>
<div id="fs_affiliation_content_wrapper" class="wrap">
    <form method="post" action="">
        <div id="poststuff">
            <div class="postbox">
                <div class="inside">
                    <div id="messages">
                        <div id="error_message" class="error" style="display: none">
                            <p><strong></strong></p>
                        </div>
                        <div id="message" class="updated" style="display: none">
                            <p><strong></strong></p>
                        </div>
                        <?php if ( $is_affiliate ) : ?>
                            <?php if ( $affiliate->is_active() ) : ?>
                                <div class="updated">
                                    <p><strong><?php
                                        printf(
                                            fs_text( 'affiliate-application-accepted', $slug ),
                                            $plugin_title,
                                            sprintf( '<a href="%s" target="_blank">%s</a>', $members_dashboard_login_url, $members_dashboard_login_url )
                                        );
                                    ?></strong></p>
                                </div>
                            <?php else : ?>
                                    <?php
                                        if ( $is_pending_affiliate ) {
                                            $text_key                = 'affiliate-application-thank-you';
                                            $message_container_class = 'updated';
                                        } else if ( $affiliate->is_suspended() ) {
                                            $text_key                = 'affiliate-account-suspended';
                                            $message_container_class = 'notice notice-warning';
                                        } else if ( $affiliate->is_rejected() ) {
                                            $text_key                = 'affiliate-application-rejected';
                                            $message_container_class = 'error';
                                        }
                                    ?>
                                    <div class="<?php echo $message_container_class ?>">
                                        <p><strong><?php fs_echo( $text_key, $slug ) ?></strong></p>
                                    </div>
                            <?php endif ?>
                        <?php endif ?>
                    </div>
                    <div class="entry-content">
                        <?php if ( ! $is_affiliate ) : ?>
                            <div id="application_messages_container">
                                <p><?php printf( fs_text( 'become-an-ambassador', $slug ), $module_type ) ?></p>
                                <p><?php printf( fs_text( 'refer-new-customers', $slug ), $module_type, $commission ) ?></p>
                            </div>
                        <?php endif ?>
                        <h3><?php fs_echo( 'program-summary', $slug ) ?></h3>
                        <ul>
                            <li><?php printf( fs_text( 'commission-on-new-license-purchase', $slug ), $commission ) ?></li>
                            <?php if ( is_object( $affiliate_terms ) && $affiliate_terms->has_renewals_commission() ) : ?>
                                <li><?php printf( fs_text( 'renewals-commission', $slug ) ) ?></li>
                            <?php endif ?>
                            <?php if ( is_object( $affiliate_terms ) && ( ! $affiliate_terms->is_session_cookie() ) ) : ?>
                                <li><?php printf( fs_text( 'affiliate-tracking', $slug ), $affiliate_tracking ) ?></li>
                            <?php endif ?>
                            <?php if ( is_object( $affiliate_terms ) && $affiliate_terms->has_lifetime_commission() ) : ?>
                                <li><?php fs_echo( 'unlimited-commissions', $slug ) ?></li>
                            <?php endif ?>
                            <li><?php printf( fs_text( 'minimum-payout-amount', $slug ), $commission ) ?></li>
                            <li><?php fs_echo( 'payouts-unit-and-processing', $slug ) ?></li>
                            <li><?php fs_echo( 'commission-payment', $slug ) ?></li>
                        </ul>
                        <div id="application_form_container" <?php echo ( $is_pending_affiliate ) ? '' : 'style="display: none"' ?>>
                            <h3><?php fs_echo( 'affiliate', $slug ) ?></h3>
                            <form>
                                <?php if ( ! $fs->is_registered() ) : ?>
                                    <div class="input-container input-container-text">
                                        <label class="input-label"><?php fs_echo( 'email-address', $slug ) ?></label>
                                        <input id="email_address" type="text" value="<?php echo esc_attr( $email_address ) ?>" class="regular-text" <?php echo $readonly ?>>
                                    </div>
                                <?php endif ?>
                                <div class="input-container input-container-text">
                                    <label class="input-label"><?php fs_echo( 'full-name', $slug ) ?></label>
                                    <input id="full_name" type="text" value="<?php echo esc_attr( $full_name ) ?>" class="regular-text" <?php echo $readonly ?>>
                                </div>
                                <div class="input-container input-container-text">
                                    <label class="input-label"><?php fs_echo( 'paypal-account-email-address', $slug ) ?></label>
                                    <input id="paypal_email" type="text" value="<?php echo esc_attr( $paypal_email_address ) ?>" class="regular-text" <?php echo $readonly ?>>
                                </div>
                                <?php if ( ! $affiliate_terms->is_any_site_allowed ) : ?>
                                    <div class="input-container input-container-text">
                                        <label class="input-label"><?php fs_echo( 'domain-field-label', $slug ) ?></label>
                                        <input id="domain" type="text" value="<?php echo esc_attr( $domain ) ?>" class="domain regular-text" <?php echo $readonly ?>>
                                        <p class="description"><?php fs_echo( 'domain-field-desc', $slug ) ?></p>
                                        <?php if ( ! $is_affiliate ) : ?>
                                            <a id="add_domain" href="#" class="disabled">+ <?php fs_echo( 'add-another-domain', $slug ) ?>...</a>
                                        <?php endif ?>
                                    </div>
                                    <div id="extra_domains_container" class="input-container input-container-text" <?php echo $is_pending_affiliate ? '' : 'style="display: none"' ?>>
                                        <label class="input-label"><?php fs_echo( 'extra-domain-fields-label', $slug ) ?></label>
                                        <p class="description"><?php fs_echo( 'extra-domain-fields-desc', $slug ) ?></p>
                                        <?php if ( $is_pending_affiliate && ! empty( $extra_domains ) ) : ?>
                                            <?php foreach ( $extra_domains as $extra_domain ) : ?>
                                                <input type="text" value="<?php echo esc_attr( $extra_domain ) ?>" class="domain regular-text" <?php echo $readonly ?>>
                                            <?php endforeach ?>
                                        <?php endif ?>
                                    </div>
                                <?php endif ?>
                                <div class="input-container">
                                    <label class="input-label"><?php fs_echo( 'promotion-methods', $slug ) ?></label>
                                    <div>
                                        <input id="promotion_method_social_media" type="checkbox" <?php checked( $promotion_method_social_media ) ?> <?php disabled( $is_affiliate ) ?>/>
                                        <label for="promotion_method_social_media"><?php fs_echo( 'social-media', $slug ) ?></label>
                                    </div>
                                    <div>
                                        <input id="promotion_method_mobile_apps" type="checkbox" <?php checked( $promotion_method_mobile_apps ) ?> <?php disabled( $is_affiliate ) ?>/>
                                        <label for="promotion_method_mobile_apps"><?php fs_echo( 'mobile-apps', $slug ) ?></label>
                                    </div>
                                </div>
                                <div class="input-container input-container-text">
                                    <label class="input-label"><nobr><?php fs_echo( 'statistics-information-field-label', $slug ) ?></nobr></label>
                                    <textarea id="statistics_information" rows="5" <?php echo $readonly ?> class="regular-text"><?php echo $statistics_information ?></textarea>
                                    <?php if ( ! $is_affiliate ) : ?>
                                        <p class="description"><?php fs_echo( 'statistics-information-field-desc', $slug ) ?></p>
                                    <?php endif ?>
                                </div>
                                <div class="input-container input-container-text">
                                    <label class="input-label"><?php fs_echo( 'promotion-method-desc-field-label', $slug ) ?></label>
                                    <textarea id="promotion_method_description" rows="5" <?php echo $readonly ?> class="regular-text"><?php echo $promotion_method_description ?></textarea>
                                    <?php if ( ! $is_affiliate ) : ?>
                                        <p class="description"><?php printf( fs_text( 'promotion-method-desc-field-desc', $slug ), $plugin_title ) ?></p>
                                    <?php endif ?>
                                </div>
                            </form>
                        </div>
                        <?php if ( ! $is_affiliate ) : ?>
                            <a id="cancel_button" href="#" class="button button-secondary button-cancel" style="display: none"><?php fs_echo( 'cancel', $slug ) ?></a>
                            <a id="submit_button" class="button button-primary" title="<?php fs_esc_attr_echo( 'apply-to-become-an-affiliate', $slug ) ?>" href="#" style="display: none"><?php fs_echo( 'apply-to-become-an-affiliate', $slug ); ?></a>
                            <a id="apply_button" class="button button-primary" title="<?php fs_esc_attr_echo( 'become-an-affiliate', $slug ) ?>" href="#"><?php fs_echo( 'become-an-affiliate', $slug ); ?></a>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <script type="text/javascript">
        jQuery(function( $ ) {
            var
                $contentWrapper           = $( '#fs_affiliation_content_wrapper' ),
                $socialMedia              = $( '#promotion_method_social_media' ),
                $mobileApps               = $( '#promotion_method_mobile_apps' ),
                $applyButton              = $( '#apply_button' ),
                $submitButton             = $( '#submit_button' ),
                $cancelButton             = $( '#cancel_button' ),
                $applicationFormContainer = $( '#application_form_container' ),
                $messageContainer         = $( '#message' ),
                $errorMessageContainer    = $( '#error_message' ),
                $domain                   = $( '#domain' ),
                $addDomain                = $( '#add_domain' ),
                $extraDomainsContainer    = $( '#extra_domains_container'),
                isAnySiteAllowed          = <?php echo $affiliate_terms->is_any_site_allowed ? 'true' : 'false' ?>;

            $applyButton.click(function( evt ) {
                evt.preventDefault();

                var $this = $( this );
                $this.hide();

                $applicationFormContainer.show();
                $cancelButton.show();
                $submitButton.show();

                $contentWrapper.find( 'input[type="text"]:first' ).focus();
            });

            $submitButton.click(function( evt ) {
                evt.preventDefault();

                var $this = $( this );

                if ( $this.hasClass( 'disabled' ) ) {
                    return;
                }

                $errorMessageContainer.hide();

                var
                    $emailAddress      = $( '#email_address' ),
                    emailAddress       = null,
                    paypalEmailAddress = $( '#paypal_email' ).val().trim();

                if ( ! isAnySiteAllowed ) {
                    var
                        $domains     = $extraDomainsContainer.find( '.domain' ),
                        domain       = $domain.val().trim(),
                        extraDomains = [];

                    if ( 0 === domain.length ) {
                        showErrorMessage( '<?php fs_echo( 'domain-is-required', $slug ) ?>' );
                        return;
                    }

                    if ( $domains.length > 0 ) {
                        $domains.each(function() {
                            var domain = $( this ).val().trim();
                            if ( 0 === domain.length ) {
                                return;
                            }

                            extraDomains.push( domain );
                        });
                    }
                }

                if ( 1 === $emailAddress.length ) {
                    emailAddress = $emailAddress.val().trim();

                    if ( 0 === emailAddress.length ) {
                        showErrorMessage( '<?php fs_echo( 'email-address-is-required', $slug ) ?>' );
                        return;
                    }
                }

                if ( 0 === paypalEmailAddress.length ) {
                    showErrorMessage( '<?php fs_echo( 'paypal-email-address-is-required', $slug ) ?>' );
                    return;
                }

                var
                    promotionMethods           = [],
                    statisticsInformation      = $( '#statistics_information' ).val(),
                    promotionMethodDescription = $( '#promotion_method_description' ).val();

                if ( $socialMedia.attr( 'checked' ) ) {
                    promotionMethods.push( 'social_media' );
                }

                if ( $mobileApps.attr( 'checked' ) ) {
                    promotionMethods.push( 'mobile_apps' );
                }

                var affiliate = {
                    full_name                   : $( '#full_name' ).val().trim(),
                    paypal_email                : paypalEmailAddress,
                    stats_description           : statisticsInformation,
                    promotion_method_description: promotionMethodDescription
                };

                if ( null !== emailAddress ) {
                    affiliate.email = emailAddress;
                }

                if ( ! isAnySiteAllowed ) {
                    affiliate.domain             = domain;
                    affiliate.additional_domains = extraDomains;
                }

                if ( promotionMethods.length > 0 ) {
                    affiliate.promotion_methods = promotionMethods.join( ',' );
                }

                $.ajax({
                    url       : ajaxurl,
                    method    : 'POST',
                    data      : {
                        action   : '<?php echo $fs->get_ajax_action( 'submit_affiliate_application' ) ?>',
                        security : '<?php echo $fs->get_ajax_security( 'submit_affiliate_application' ) ?>',
                        slug     : '<?php echo $slug ?>',
                        affiliate: affiliate
                    },
                    beforeSend: function() {
                        $cancelButton.addClass( 'disabled' );
                        $submitButton.addClass( 'disabled' );
                        $submitButton.text( '<?php fs_echo( 'processing' ) ?>' );
                    },
                    success   : function( result ) {
                        if ( result.success ) {
                            $messageContainer.find( 'strong' ).text( '<?php fs_esc_js_echo( 'affiliate-application-thank-you', $slug ) ?>' );
                            $messageContainer.show();

                            $contentWrapper.find( 'input[type="text"], textarea' ).prop( 'readonly', true );
                            $contentWrapper.find( 'input[type="checkbox"]' ).prop( 'disabled', true );
                            $contentWrapper.find( '.description' ).hide();

                            $( '#application_messages_container' ).hide();

                            if ( ! isAnySiteAllowed ) {
                                $addDomain.hide();
                            }

                            $cancelButton.hide();
                            $submitButton.hide();
                        } else if ( result.error && result.error.length > 0 ) {
                            showErrorMessage( result.error );
                        }

                        window.scrollTo( 0, 0 );
                    },
                    complete  : function() {
                        $cancelButton.removeClass( 'disabled' );
                        $submitButton.removeClass( 'disabled' );
                        $submitButton.text( '<?php fs_echo( 'apply-to-become-an-affiliate', $slug ) ?>' )
                    }
                });
            });

            $cancelButton.click(function( evt ) {
                evt.preventDefault();

                var $this = $( this );

                if ( $this.hasClass( 'disabled' ) ) {
                    return;
                }

                $applicationFormContainer.hide();
                $this.hide();
                $submitButton.hide();

                $applyButton.show();

                window.scrollTo( 0, 0 );
            });

            if ( ! isAnySiteAllowed ) {
                $domain.on( 'input propertychange', onDomainChange );

                $addDomain.click(function( evt ) {
                    evt.preventDefault();

                    var
                        $this  = $( this ),
                        domain = $domain.val().trim();

                    if ( $this.hasClass( 'disabled' ) || 0 === domain.length ) {
                        return;
                    }

                    $domain.off( 'input propertychange' );
                    $this.addClass( 'disabled' );

                    var $emptyDomainField = $( '<input type="text" class="domain regular-text"/>' );
                    $emptyDomainField.on( 'input propertychange', onDomainChange );

                    $extraDomainsContainer.show();

                    $emptyDomainField.appendTo( $extraDomainsContainer ).focus();
                    $this.appendTo( $extraDomainsContainer );
                });
            }

            /**
             * @author Leo Fajardo (@leorw)
             */
            function onDomainChange() {
                var
                    domain = $( this ).val().trim();

                if ( domain.length > 0 ) {
                    $addDomain.removeClass( 'disabled' );
                } else {
                    $addDomain.addClass( 'disabled' );
                }
            }

            /**
             * @author Leo Fajardo (@leorw)
             *
             * @param {String} message
             */
            function showErrorMessage( message ) {
                $errorMessageContainer.find( 'strong' ).text( message );
                $errorMessageContainer.show();

                window.scrollTo( 0, 0 );
            }
        });
    </script>
</div>
<?php
    $params = array(
        'page'           => 'affiliation',
        'module_id'      => $fs->get_id(),
        'module_slug'    => $slug,
        'module_version' => $fs->get_plugin_version(),
    );
    fs_require_template( 'powered-by.php', $params );
?>