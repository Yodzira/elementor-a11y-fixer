<?php
/**
 * Plugin boot + admin.
 *
 * @package ElementorA11yFixer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EAF_Plugin {

	public static function boot() {
		if ( is_admin() ) {
			add_action( 'admin_menu', array( 'EAF_Admin', 'menu' ) );
			add_action( 'admin_post_eaf_save', array( 'EAF_Admin', 'handle_save' ) );
		}
		EAF_Front::boot();
	}
}

class EAF_Admin {

	public static function menu() {
		add_menu_page( 'Elementor A11y', 'Elementor A11y', 'manage_options', 'elementor-a11y-fixer', array( __CLASS__, 'render' ), 'dashicons-editor-table' );
	}

	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'elementor-a11y-fixer' ) );
		}
		check_admin_referer( 'eaf_save' );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized in Settings::save().
		EAF_Settings::save( isset( $_POST['eaf'] ) ? (array) $_POST['eaf'] : array() );
		wp_safe_redirect( admin_url( 'admin.php?page=elementor-a11y-fixer&saved=1' ) );
		exit;
	}

	/**
	 * Preview: run the engine over the homepage HTML and list the changes.
	 *
	 * @return void
	 */
	public static function handle_preview() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
		}
		check_ajax_referer( 'eaf_preview', 'nonce' );

		$response = wp_remote_get( home_url( '/' ), array( 'timeout' => 15, 'sslverify' => true ) );
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			wp_send_json_error( array( 'message' => 'Homepage could not be fetched.' ) );
		}

		$settings = EAF_Settings::get();
		$result   = EAF_Engine::run(
			(string) wp_remote_retrieve_body( $response ),
			$settings['repairs'],
			array( 'alt_lookup' => array( 'EAF_Lookup', 'alt_by_url' ) )
		);

		wp_send_json_success(
			array(
				'is_elementor' => EAF_Parser::is_elementor( (string) wp_remote_retrieve_body( $response ) ),
				'total'        => count( $result['changes'] ),
				'changes'      => $result['changes'],
			)
		);
	}

	public static function render() {
		$settings = EAF_Settings::get();
		$saved    = isset( $_GET['saved'] ) ? sanitize_key( wp_unslash( $_GET['saved'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display flag.
		?>
		<div class="wrap">
			<h1>Elementor A11y Fixer</h1>

			<?php if ( '1' === $saved ) : ?>
				<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="eaf_save">
				<?php wp_nonce_field( 'eaf_save' ); ?>

				<p><label><input type="checkbox" name="eaf[master_enabled]" value="1" <?php checked( $settings['master_enabled'] ); ?>> <strong>Apply repairs to visitors' pages</strong></label></p>

				<table class="widefat striped" style="max-width:900px">
					<thead><tr><th style="width:60px">On</th><th>Repair</th></tr></thead>
					<tbody>
					<?php foreach ( EAF_Engine::repairs() as $id => $repair ) : ?>
						<tr>
							<td><input type="checkbox" name="eaf[repairs][<?php echo esc_attr( $id ); ?>]" value="1" <?php checked( ! empty( $settings['repairs'][ $id ] ) ); ?>></td>
							<td>
								<strong><?php echo esc_html( $repair['label'] ); ?></strong>
								<?php if ( $repair['risky'] ) : ?><span style="color:#dba617">● review before enabling</span><?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<p style="margin-top:14px">
					<button type="submit" class="button button-primary">Save settings</button>
					<button type="button" class="button" id="eaf-preview-btn">Preview on homepage</button>
					<span id="eaf-preview-result" style="margin-left:10px"></span>
				</p>
			</form>

			<div id="eaf-preview-table" style="max-width:900px"></div>
		</div>

		<script>
		(function () {
			var btn = document.getElementById('eaf-preview-btn');
			btn && btn.addEventListener('click', function () {
				var out = document.getElementById('eaf-preview-result');
				out.textContent = '…';
				var body = new FormData();
				body.append('action', 'eaf_preview');
				body.append('nonce', '<?php echo esc_js( wp_create_nonce( 'eaf_preview' ) ); ?>');
				fetch(window.ajaxurl, { method: 'POST', credentials: 'same-origin', body: body })
					.then(function (r) { return r.json(); })
					.then(function (json) {
						if (!json.success) { out.textContent = '❌ ' + (json.data && json.data.message ? json.data.message : 'Failed'); return; }
						out.textContent = json.data.is_elementor ? ('✅ ' + json.data.total + ' change(s) on the homepage') : '⚠️ Elementor markup not detected on the homepage.';
						var rows = (json.data.changes || []).map(function (c) {
							return '<tr><td>' + c.repair + '</td><td><code>' + c.attr + '</code></td><td>' + (c.after || '') + '</td></tr>';
						}).join('');
						document.getElementById('eaf-preview-table').innerHTML = rows
							? '<table class="widefat striped"><thead><tr><th>Repair</th><th>Attribute</th><th>Value</th></tr></thead><tbody>' + rows + '</tbody></table>'
							: '';
					});
			});
		})();
		</script>
		<?php
	}
}
