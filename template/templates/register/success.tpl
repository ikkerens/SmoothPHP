{include "base.tpl"}

{block "main"}
    <p>You have successfully created your account.</p>
    <p>You can now log in <a href="{$route->buildPath('front_login')}">here</a>.</p>
{/block}