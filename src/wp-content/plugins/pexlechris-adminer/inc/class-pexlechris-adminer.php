<?php

class Pexlechris_Adminer extends Adminer {

    public function get_wp_locale()
	{
		$wp_user_locale = get_user_locale();
        $expl = explode('_', $wp_user_locale);
		$adminer_locale = $expl[0];

		/**
         * Filter the locale of Adminer UI.
         *
		 * @since 3.1.0
         *
         * @param string $adminer_locale
		 */
        return apply_filters('pexlechris_adminer_locale', $adminer_locale);
	}

	function credentials() {
		// server, username and password for connecting to database
		return array(DB_HOST, DB_USER, DB_PASSWORD);
	}

	function login($login, $password) {
		return true; // login even if password is empty string
	}

    function head(){
        $this->pexlechris_adminer_head();
		/**
		 * If a developer want to add just JS and/or CSS in head, he/she can just use the action pexlechris_adminer_head.
		 * See plugin's FAQs, for more.
		 */
		do_action('pexlechris_adminer_head');
		return true;
    }

	function pexlechris_adminer_head()
	{
		?>
		<script nonce="<?php echo esc_attr( get_nonce() )?>"> // get_nonce is an adminer function
            verifyVersion = function () {}; // Disable version checker

            // auto login
            window.addEventListener('load', function(){

                if ( null === document.querySelector('.pexle_loginForm') ) return;

                var wpLocale = '<?php echo $this->get_wp_locale(); ?>';

                var langExists = !!document.querySelector( '#lang option[value="' + wpLocale + '"]' );
                var selectElement = document.querySelector('#lang select');

                if( langExists && selectElement.value != wpLocale ){
                    selectElement.value = wpLocale;
                    var event = new Event('change', { bubbles: true });
                    selectElement.dispatchEvent(event);

                }else{
                    document.querySelector('.pexle_loginForm + p > input').click();
                }

            });
		</script>

		<style>
            #lang,
            .pexle_loginForm *,
            .pexle_loginForm + p,
            #tables a.select,
            #version,
            p.logout {
                display: none;
            }
            .pexle_loginForm::before {
                content: "<?php esc_html_e('You are connecting to the database...', 'pexlechris-adminer'); ?>";
            }

            #menu{
                margin-top: 0;
                top: 0
            }
            #menu > h1{
                border-top: 0;
            }

			<?php if( !defined('PEXLECHRIS_ADMINER_HAVE_ACCESS_ONLY_IN_WP_DB') || true === PEXLECHRIS_ADMINER_HAVE_ACCESS_ONLY_IN_WP_DB ): ?>
                #breadcrumb > a:nth-child(2){
                    width: 17px;
                    display: inline-block;
                    margin-left: -14px;
                    color: transparent;
                    background: #eee;
                    margin-right: -23px;
                    pointer-events: none;
                }
                #dbs{
                    display: none;
                }
                .footer > div > fieldset > div > p{
                    width: 150px;
                    color: transparent;
                    display: inline-block;
                }
                .footer > div > fieldset > div > p > *:not([name="copy"]){
                    display: none;
                }
                .footer > div > fieldset > div > p > [name="copy"]{
                    float: left;
                }
			<?php endif; ?>

		</style>
		<?php
	}
}