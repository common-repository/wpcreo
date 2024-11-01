<div class="wrap">

    <?php screen_icon(); ?>

    <form action="options.php" method="post" id="<?php echo $plugin_id; ?>_options_form" name="<?php echo $plugin_id; ?>_options_form">

        <?php settings_fields($plugin_id.'_options'); ?>

        <h2>WPCREO &raquo; Settings</h2>
        <table class="widefat">
            <tbody>
            <tr>
                <td style="padding:25px;font-family:Verdana, Geneva, sans-serif;color:#666;">
                    <label for="magazineUrl">
                         Please enter your WPCREO Magazine Url
                    </label>
                    <p>
                        <input type="text" id="magazineUrl" name="magazineUrl" value="<?php echo get_option('magazineUrl'); ?>" style="width:100%" />
                    </p>
                </td>
            </tr>
      </tbody>
            <tfoot>
            <tr>
                <th>

                    <input type="submit" name="submit" value="Save Settings" class="button-primary" onclick="return urlValidation();" />

                    <input type="submit" name="sync" value="Sync All Articles" class="button-primary" onclick="setMode();return false;" style="margin-left: 20px" />            
                </th>
            </tr>
      </tfoot>
        </table>
        
    </form>
    <script type="text/javascript">
        function setMode() {

            if (urlValidation()) {
                
                var data = {
                    action: 'syncAllPost'
                };
                jQuery.post(ajaxurl, data, function (response) {
                    alert('Got this from the server: ' + response);
                });

                alert('We will run this process in the background! This process can take some time, depending on the number of posts that you are syncing!');
            }
        }

        function urlValidation() {
            var url = document.getElementById('magazineUrl').value.toLowerCase();
            var isValid = (url.indexOf('http://') == 0 && url.indexOf('full=1') > 0 && url.indexOf('/services/') > 0);

            if (!isValid)
                alert('Please enter a valid WPCREO Plugin URL!');

            return isValid;
        }
    </script>
</div>