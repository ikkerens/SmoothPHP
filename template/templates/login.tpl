{include "base.tpl"}

{block "main"}
    <div>{$language->hello_world}</div>
    <ul>
        {if !$form->isValid()}
            {foreach $form->getErrorMessages() as $error}
                <li class="error">{$error}</li>
            {/foreach}
        {/if}
    </ul>
    {$form}
    <p>Alternatively you can register <a href="{$route->buildPath('register')}">here</a>.</p>
{/block}