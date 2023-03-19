{if !isset($smarty.cookies.age_verified)}
  <form action="" method="post">
    <div class="popup-fade">
      <div class="popup">
        <div>
          <label for="date-input">{__("enter_your_date_of_birth")}:</label>
          <input type="date" id="date-input" name="birth_date">
        </div>
            {include 
                file="buttons/button.tpl" 
                but_name="dispatch[age_ver]" 
                but_text=__("submit") 
                but_role="submit-link" 
                but_meta="ty-btn__primary ty-btn__big cm-form-dialog-closer ty-btn"
            }
      </div>		
    </div>
  </form>
{/if}
  {if $smarty.cookies.age_verified === 'false'}
    <div class="age-ver-overlay visible">
      <div class="age-ver-popup">
        <h2>{__("access_closed")}</h2>
        <p>{__("under_18")}</p>
      </div>
    </div>
{/if}


