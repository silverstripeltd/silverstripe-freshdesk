<form $FormAttributes>
    <div>
        <span>Status:</span>
        <fieldset>
            $Fields.dataFieldByName(status)
        </fieldset>
        <span>Priority:</span>
        <fieldset>
            $Fields.dataFieldByName(priority)
        </fieldset>
    </div>
    <div class="Actions">
        <% loop $Actions %>$Field<% end_loop %>
    </div>
</form>
