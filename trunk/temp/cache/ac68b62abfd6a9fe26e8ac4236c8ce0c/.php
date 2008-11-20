a:2:{s:4:"time";d:1227079474.5386049747467041015625;s:4:"data";s:3123:"
    <form method="post" id="test" action="/Bento/index.php?engine=Admin&action=Test&package=BentoBase">
    
        <input type="hidden" value="test" id="test_formIdentifier" name="formIdentifier">
    
        <fieldset id="test_section_readonlyDatabase">
        
            <legend>
                Read Only Database Connection

            </legend>
        
            <div class='intro'>
                This is the read only database connection, which all of the select statements use. If you do not have a seperate user for this you may leave it blank.

            </div>
        
            <label for="test_DBROname" id="test_DBROname_label">
                Database

            </label>
        
            <input id="test_DBROname" name="DBROname"></input>
        
            <br>
        
            <label for="test_DBROusername" id="test_DBROusername_label">
                User

            </label>
        
            <input id="test_DBROusername" name="DBROusername"></input>
        
            <br>
        
            <label for="test_DBROpassword" id="test_DBROpassword_label">
                Password

            </label>
        
            <input id="test_DBROpassword" name="DBROpassword"></input>
        
            <br>
        
            <label for="test_DBROhost" id="test_DBROhost_label">
                Host

            </label>
        
            <input value="localhost" id="test_DBROhost" name="DBROhost"></input>
        
            <br>

        </fieldset>
    
        <fieldset id="test_section_mainDatabase">
        
            <legend>
                Main Database Connection

            </legend>
        
            <div class='intro'>
                This is the primary database connection. This user needs to have full access to the database.

            </div>
        
            <label for="test_DBname" id="test_DBname_label">
                Database

            </label>
        
            <input class='{"validation":{"required":true}}' id="test_DBname" name="DBname"></input>
        
            <br>
        
            <label for="test_DBusername" id="test_DBusername_label">
                User

            </label>
        
            <input class='{"validation":{"required":true}}' id="test_DBusername" name="DBusername"></input>
        
            <br>
        
            <label for="test_DBpassword" id="test_DBpassword_label">
                Password

            </label>
        
            <input class='{"validation":{"required":true}}' id="test_DBpassword" name="DBpassword"></input>
        
            <br>
        
            <label for="test_DBhost" id="test_DBhost_label">
                Host

            </label>
        
            <input class='{"validation":{"required":true}}' value="localhost" id="test_DBhost" name="DBhost"></input>
        
            <br>

        </fieldset>
    
        <div id="test_section_control">
        
            <label></label>
        
            <input name="Submit" type="Submit" value="Submit"></input>
            <br>

        </div>

    </form>";}