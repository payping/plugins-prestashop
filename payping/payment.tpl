<!-- PayPing Payment Module -->
<p class="payment_module">
    <a href="javascript:$('#payping').submit();" title="{l s='Online payment with PayPing' mod='payping'}">
        <img src="{l s='link' mod='payping'}modules/payping/payping.png" alt="{l s='Online payment with PayPing' mod='payping'}" style="margin: 25px;" />
		{l s='Online payment with PayPing site' mod='payping'}
<br>
</a></p>

<form action="{l s='link' mod='payping'}modules/payping/pfunction.php?do=payment" method="post" id="payping" class="hidden">
    <input type="hidden" name="orderId" value="{$orderId}" />
</form>
<br><br>
<!-- End of PayPing Payment Module-->
