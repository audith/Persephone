<div class="aside full_size" id="system_console">Required fields (in red) must be filled-in!</div>
<ul class="section full_size">
	<li>
		<h2 class="full_size">Register
			<span class="description"></span>
		</h2>

		<p>Already registered? Sign-in <a href="/users/login">here</a>.</p>

		{{if $CONFIG.security.no_reg}}

			<p>New registrations has been disabled by site administrator!</p>

		{{else}}

			<form action="" method="post" id="forms__register">

			<h3 class="start_group">Required information</h3>

			<fieldset class="deco_1">
				<label title="Choose a Username" for="register__username"><strong>Username</strong><em>The name you'll sign in with. You can't use: [ ] | ; , $ \ &lt; &gt;</em></label>
				<input type="text" class="text required" id="register__username" name="username" />

				<label title="Choose a Display name" for="register__display_name"><strong>Display-name</strong><em>The name that will be displayed. This should be between 3 and 26 characters long.</em></label>
				<input type="text" class="text required" id="register__display_name" name="display_name" />

				<label title="Enter e-mail address" for="register__email"><strong>E-mail address</strong><em>So that we can verify your identity, and keep you updated.</em></label>
				<input type="text" class="text required" id="register__email" name="email" />

				<label title="Confirm e-mail address" for="register__email_repeat"><strong>Confirm e-mail address</strong><em>&nbsp;</em></label>
				<input type="text" class="text required" id="register__email_repeat" name="email_repeat" />

				<label title="Choose your Password" for="register__password"><strong>Password</strong><em>You should choose a strong password, between 3 and 32 characters</em></label>
				<input type="password" class="text required" id="register__password" name="password" />

				<label title="Re-enter your Password" for="register__password_repeat"><strong>Re-enter Password</strong><em>&nbsp;</em></label>
				<input type="password" class="text required" id="register__password_repeat" name="password_repeat" />
			</fieldset>
			<div class="closing"></div>

			{{if $CONFIG.security.enable_captcha}}
			<h3 class="start_group">Verify you are human</h3>
			<fieldset class="deco_1">
				<span class="input full_size" id="register__captcha">{{recaptcha}}</span>
			</fieldset>
			<div class="closing"></div>
			{{/if}}

			<h3 class="start_group">Terms of Service</h3>
			<fieldset class="deco_1">
				<span class="input full_size">
					<textarea class="full_size tinymce" cols="" rows=""><p>Test <b>Bold</b></p></textarea>
				</span>

				<span class="input full_size" style="font-size:14px; font-weight:bold;">
					<input type="checkbox" name="terms_agree" value="1" id="tos_agree_checkbox" style="float:left; line-height:1em;" />
					<label for="tos_agree_checkbox" style="float:left; margin-left:1% !important; line-height:1em;">I agree to the following Terms of Service!</label>
				</span>
			</fieldset>
			<div class="closing"></div>


			<p>
			<span class="buttons">
				<input type="hidden" name="do" value="do_register" />
				<input type="hidden" name="referer" value="{{$CONTENT.referer}}" />
				<input type="submit" value="Register" />
			</span>
			</p>
			</form>

		{{/if}}
	</li>
</ul>