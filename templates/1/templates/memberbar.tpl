{{if $MEMBER.id}}
<ul>
<li>Logged-in As: <a href="/users/profile-{{$MEMBER.id}}-{{$MEMBER.seo_name}}" title="View profile for {{$MEMBER.display_name}}" id="links__memberbar__profile">{{$MEMBER.display_name}}</a></li>
<li><a href="/users/logout?referer={{$PAGE.request.request_uri|urlencode}}" title="Logout" rel="nofollow">Logout</a></li>
</ul>
{{else}}
<ul>
<li><a href="/users/login" title="Login" rel="nofollow">Login</a></li>
<li><a href="/users/lostpass" title="Lost Password?" rel="nofollow">Lost Password?</a></li>
<li><a href="/users/register" title="Register" rel="nofollow">Register</a></li>
</ul>
{{/if}}