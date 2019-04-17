{include "base.tpl"}

{block "title" prepend}Secure page - {/block}

{block "main"}
    <p>This is a secure page!</p>
    <p>Here is your avatar:</p>
    <p><img src="{$route->buildPath('avatar')}"/></p>
    <p><a href="{$route->buildPath('logout')}">Click here to log out</a></p>
{/block}