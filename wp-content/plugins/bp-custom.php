<?php
define('BP_DISABLE_ADMIN_BAR', true);

function cac_email_activity_checkbox() {
	global $bp;
	global $current_user;

	if ( !bp_is_groups_component() )
		return;
	if ( !is_super_admin() && $current_user->roles[0] !== 'author' )
		if ( $bp->groups->current_group->name === '社員会' )
			return;
	?>

	<div id="activity_mail">
		<label for="cac_activity_mail">
			グループメンバーに投稿内容をメールする
			<input type="checkbox" name="cac_activity_mail" id="cac_activity_mail" value="mailme" />
		</label>
	</div>
	<?php
}
add_action( 'bp_activity_post_form_options', 'cac_email_activity_checkbox' );

function cac_email_activity_handler( $activity ) {
	global $bp;

	if ( $_POST['mailme'] == 'mailme' ) {

		$subject = sprintf('[%s] %sグループに投稿がありました', esc_html( get_option( 'blogname' ) ), $bp->groups->current_group->name );
		$message = strip_tags( $activity->action );
		$message .= '

';
		$message .= strip_tags( $activity->content );
		$message .= '

-------
';

		$message .= sprintf('このメールは、%sグループのメンバー全員に送信されています。投稿ページへは以下のリンクからアクセスできます: %s', $bp->groups->current_group->name, $bp->root_domain . '/groups/' . $bp->groups->current_group->slug . '/' );

		$subject = mb_convert_encoding( $subject, get_option( 'blog_charset' ), $charset );
		$message = mb_convert_encoding( $message, get_option( 'blog_charset' ), $charset );

		if ( bp_group_has_members( 'exclude_admins_mods=0&amp;per_page=10000' ) ) {
			global $members_template;
			foreach( $members_template->members as $m ) {
				wp_mail( $m->user_email, $subject, $message );
			}
		}
	}

	remove_action( 'bp_activity_after_save' , 'ass_group_notification_activity' , 50 );
}
add_action( 'bp_activity_after_save', 'cac_email_activity_handler', 1 );

function cac_email_activity_js() {
	if ( !bp_is_groups_component() )
		return;
	?>
	<script type="text/javascript">

		var jq = jQuery;
		jq(document).ready( function() {
			jq("input#aw-whats-new-submit").unbind('click');
			/* New posts */
			jq("input#aw-whats-new-submit").click( function() {
				var button = jq(this);
				var form = button.parent().parent().parent().parent();

				form.children().each( function() {
					if ( jq.nodeName(this, "textarea") || jq.nodeName(this, "input") )
						jq(this).attr( 'disabled', 'disabled' );
					});

				jq( 'form#' + form.attr('id') + ' span.ajax-loader' ).show();

				/* Remove any errors */
				jq('div.error').remove();
				button.attr('disabled','disabled');

				/* Default POST values */
				var object = '';
				var item_id = jq("#whats-new-post-in").val();
				var content = jq("textarea#whats-new").val();
				var mailme = jq("#cac_activity_mail:checked").val();

				/* Set object for non-profile posts */
				if ( item_id > 0 ) {
					object = jq("#whats-new-post-object").val();
				}

				jq.post( ajaxurl, {
					action: 'post_update',
					'cookie': encodeURIComponent(document.cookie),
					'_wpnonce_post_update': jq("input#_wpnonce_post_update").val(),
					'content': content,
					'object': object,
					'mailme': mailme,
					'item_id': item_id
				},
				function(response)
				{
					jq( 'form#' + form.attr('id') + ' span.ajax-loader' ).hide();

					form.children().each( function() {
						if ( jq.nodeName(this, "textarea") || jq.nodeName(this, "input") )
						jq(this).attr( 'disabled', '' );
					});

					/* Check for errors and append if found. */
					if ( response[0] + response[1] == '-1' ) {
						form.prepend( response.substr( 2, response.length ) );
						jq( 'form#' + form.attr('id') + ' div.error').hide().fadeIn( 200 );
						button.attr("disabled", '');
					} else {
						if ( 0 == jq("ul.activity-list").length ) {
							jq("div.error").slideUp(100).remove();
							jq("div#message").slideUp(100).remove();
							jq("div.activity").append( '<ul id="activity-stream" class="activity-list item-list">' );
						}

						jq("ul.activity-list").prepend(response);
						jq("ul.activity-list li:first").addClass('new-update');
						jq("li.new-update").hide().slideDown( 300 );
						jq("li.new-update").removeClass( 'new-update' );
						jq("textarea#whats-new").val('');
						jq("#cac_activity_mail").removeAttr('checked');

						/* Re-enable the submit button after 8 seconds. */
						setTimeout( function() { button.attr("disabled", ''); }, 8000 );
					}
				});

				return false;
			});
		});

	</script>
<?php
}
add_action( 'bp_activity_post_form_options', 'cac_email_activity_js', 999 );

?>
