<?php
/* Copyright (c) 2005, Axis Data Management Corp.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * Redistributions of source code must retain the above copyright notice, this
 * list of conditions and the following disclaimer.
 *
 * Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 *
 * Neither the name of Axis Data Management Corp nor the names of its
 * contributors may be used to endorse or promote products derived from this
 * software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL AXIS DATA MANAGEMENT CORP
 * OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 *
 * Authorize.net AIM client.
 *
 * N.b. the difference between "transaction key", my password; and
 * "transaction ID", the identifier for a transaction.
 *
 * !!!! DO NOT USE THIS CLIENT WITHOUT VERIFYING THE CHECKSUM. !!!!
 * It would be very easy for some jerk to modify this script to record
 * your credit card numbers, redirect your requests to another server,
 * or do other nasty things.
 *
 * This is a PHP port of the Java class com.admc.lm.AIM, which I wrote
 * from scratch.
 *
 * This class does not yet support the AIM MD5 Hash feature.
 * (Since the entire transaction is SSL-encrypted with Authorize.net's
 * certificate, IMO it's unnecessary).
 *
 * SETUP:  See the constructor for this class below.
 *         Until the setup is made more user friendly, edit the file
 *         'aim-settings.php' according to the comments in that file.
 *
 * @author Blaine Simpson  blaine.simpson@admc.com
 */ 


class AIM {
    const MAX_RESPONSE_SIZE = 10240;
    const MON_FORMATSTR = "%6.2f";

    private $urlString = null;
    private $login = null;
    private $tran_key = null;
    private $pdelimiter = "\0";
    private $pencap = "\0";
    private $pminfields = -1;
    private $pversion = null;
    private $prelay_response = null;
    private $amount = null;
    private $card_num = null;
    private $exp_date = null;
    private $test_request = null;

    private $first_name = null;
    private $last_name = null;
    private $company = null;
    private $address = null;
    private $city = null;
    private $state = null;
    private $zip = null;
    private $country = null;
    private $phone = null;
    private $fax = null;
    private $ship_to_first_name = null;
    private $ship_to_last_name = null;
    private $ship_to_company = null;
    private $ship_to_address = null;
    private $ship_to_city = null;
    private $ship_to_state = null;
    private $ship_to_zip = null;
    private $ship_to_country = null;
    private $cust_id = null;
    private $email = null;
    private $invoice_num = null;
    private $description = null;
    private $type = null;
    private $card_code = null;
    private $trans_id = null;
    private $auth_code = null;

    public function setBillFirstName($inString) {
        $this->first_name = $inString;
        if (strlen($this->first_name) > 50) $this->first_name = substring($this->first_name, 0, 50);
    }
    public function setBillLastName($inString) {
        $this->last_name = $inString;
        if (strlen($this->last_name) > 50) $this->last_name = substring($this->last_name, 0, 50);
    }
    public function setBillOrg($inString) {
        $this->company = $inString;
        if (strlen($this->company) > 50) $this->company = substring($this->company, 0, 50);
    }
    public function setBillAddress($inString) {
        $this->address = $inString;
        if (strlen($this->address) > 60) $this->address = substring($this->address, 0, 60);
    }
    public function setBillCity($inString) {
        $this->city = $inString;
        if (strlen($this->city) > 40) $this->city = substring($this->city, 0, 40);
    }
    public function setBillStateName($inString) {
        $this->state = $inString;
        if (strlen($this->state) > 40) $this->state = substring($this->state, 0, 40);
    }
    public function setBillZip($inString) {
        $this->zip = $inString;
        if (strlen($this->zip) > 20) $this->zip = substring($this->zip, 0, 20);
    }
    public function setBillCountry($inString) {
        $this->country = $inString;
        if (strlen($this->country) > 60) $this->country = substring($this->country, 0, 60);
    }
    public function setBillPhone($inString) {
        $this->phone = $inString;
        if (strlen($this->phone) > 25) $this->phone = substring($this->phone, 0, 25);
    }
    public function setBillFax($inString) {
        $this->fax = $inString;
        if (strlen($this->fax) > 25) $this->fax = substring($this->fax, 0, 25);
    }
    public function setCustId($inString) {
        $this->cust_id = $inString;
        if (strlen($this->cust_id) > 20) $this->cust_id = substring($this->cust_id, 0, 20);
    }
    public function setEmail($inString) {
        $this->email = $inString;
        if (strlen($this->email) > 255) $this->email = substring($this->email, 0, 255);
    }
    public function setInvoiceNum($inString) {
        $this->invoice_num = $inString;
        if (strlen($this->invoice_num) > 20)
        $this->invoice_num = substring($this->invoice_num, 0, 20);
    }
    public function setDescription($inString) {
        $this->description = $inString;
        if (strlen($this->description) > 255) $this->description =
        substring($this->description, 0, 255);
    }
    public function setShipFirstName($inString) {
        $this->ship_to_first_name = $inString;
        if (strlen($this->ship_to_first_name) > 50)
        $this->ship_to_first_name = substring($this->ship_to_first_name, 0, 50);
    }
    public function setShipLastName($inString) {
        $this->ship_to_last_name = $inString;
        if (strlen($this->ship_to_last_name) > 50)
        $this->ship_to_last_name = substring($this->ship_to_last_name, 0, 50);
    }
    public function setShipOrg($inString) {
        $this->ship_to_company = $inString;
        if (strlen($this->ship_to_company) > 50)
        $this->ship_to_company = substring($this->ship_to_company, 0, 50);
    }
    public function setShipAddress($inString) {
        $this->ship_to_address = $inString;
        if (strlen($this->ship_to_address) > 60)
        $this->ship_to_address = substring($this->ship_to_address, 0, 60);
    }
    public function setShipCity($inString) {
        $this->ship_to_city = $inString;
        if (strlen($this->ship_to_city) > 40)
        $this->ship_to_city = substring($this->ship_to_city, 0, 40);
    }
    public function setShipStateName($inString) {
        $this->ship_to_state = $inString;
        if (strlen($this->ship_to_state) > 40)
        $this->ship_to_state = substring($this->ship_to_state, 0, 40);
    }
    public function setShipZip($inString) {
        $this->ship_to_zip = $inString;
        if (strlen($this->ship_to_zip) > 20)
        $this->ship_to_zip = substring($this->ship_to_zip, 0, 20);
    }
    public function setShipCountry($inString) {
        $this->ship_to_country = $inString;
        if (strlen($this->ship_to_country) > 60)
        $this->ship_to_country = substring($this->ship_to_country, 0, 60);
    }

    public function setType($inString) {
        $this->type = $inString;
    }

    public function setCardCode($inString) {
        $this->card_code = $inString;
    }

    public function setTransId($inInt) {
        $this->trans_id = strval($inInt);
    }

    public function setAuthCode($inString) {
        $this->auth_code = $inString;
    }

    public function setAmount($inAmt) {
        if (gettype($inAmt) == "integer") {
            $this->requestedAmount = $inAmt;
            $this->amount = sprintf(AIM::MON_FORMATSTR, $inAmt / 100.0);
        } else {
            $this->amount = $inAmt;
        }
    }

    private $requestedAmount = -1;

    public function setCardNum($inString) {
        $this->card_num = $inString;
    }

    public function setExp($inString) {
        $this->exp_date = $inString;
    }

    /**
     * Configures the instance.
     *
     * TODO:
     *    Read in a user-friendly configuration file, instead of a PHP
     *    source code file.
     *    The Java version of this class uses a "properties" file, but
     *    I know of no comparable feature to make this easy to do in PHP.
     */
    public function __construct() {
        if (!(require('config/aim-settings.php')))
            throw Exception("Failed to read in file 'aim-settings.php'");
    }

    public function getTestMode() {
        return $this->test_request;
    }

    //synchronized public function fetch() {
    public function fetch() {
        $this->data = array();
        if ($this->login != null) {
            $postvals['x_login'] = $this->login;
        }
        if ($this->tran_key != null) {
            $postvals['x_tran_key'] = $this->tran_key;
        }
        if ($this->pversion != null) {
            $postvals['x_version'] = $this->pversion;
        }
        if ($this->prelay_response != null) {
            $postvals['x_relay_response'] = $this->prelay_response;
        }
        if ($this->amount != null) {
            $postvals['x_amount'] = $this->amount;
        }
        if ($this->card_num != null) {
            $postvals['x_card_num'] = $this->card_num;
        }
        if ($this->exp_date != null) {
            $postvals['x_exp_date'] = $this->exp_date;
        }
        if ($this->test_request != null) {
            $postvals['x_test_request'] = $this->test_request;
        }
        if ($this->first_name != null) {
            $postvals['x_first_name'] = $this->first_name;
        }
        if ($this->last_name != null) {
            $postvals['x_last_name'] = $this->last_name;
        }
        if ($this->company != null) {
            $postvals['x_company'] = $this->company;
        }
        if ($this->address != null) {
            $postvals['x_address'] = $this->address;
        }
        if ($this->city != null) {
            $postvals['x_city'] = $this->city;
        }
        if ($this->state != null) {
            $postvals['x_state'] = $this->state;
        }
        if ($this->zip != null) {
            $postvals['x_zip'] = $this->zip;
        }
        if ($this->country != null) {
            $postvals['x_country'] = $this->country;
        }
        if ($this->phone != null) {
            $postvals['x_phone'] = $this->phone;
        }
        if ($this->fax != null) {
            $postvals['x_fax'] = $this->fax;
        }
        if ($this->cust_id != null) {
            $postvals['x_cust_id'] = $this->cust_id;
        }
        if ($this->email != null) {
            $postvals['x_email'] = $this->email;
        }
        if ($this->invoice_num != null) {
            $postvals['x_invoice_num'] = $this->invoice_num;
        }
        if ($this->description != null) {
            $postvals['x_description'] = $this->description;
        }
        if ($this->ship_to_first_name != null) {
            $postvals['x_ship_to_first_name'] = $this->ship_to_first_name;
        }
        if ($this->ship_to_last_name != null) {
            $postvals['x_ship_to_last_name'] = $this->ship_to_last_name;
        }
        if ($this->ship_to_company != null) {
            $postvals['x_ship_to_company'] = $this->ship_to_company;
        }
        if ($this->ship_to_address != null) {
            $postvals['x_ship_to_address'] = $this->ship_to_address;
        }
        if ($this->ship_to_city != null) {
            $postvals['x_ship_to_city'] = $this->ship_to_city;
        }
        if ($this->ship_to_state != null) {
            $postvals['x_ship_to_state'] = $this->ship_to_state;
        }
        if ($this->ship_to_zip != null) {
            $postvals['x_ship_to_zip'] = $this->ship_to_zip;
        }
        if ($this->ship_to_country != null) {
            $postvals['x_ship_to_country'] = $this->ship_to_country;
        }
        if ($this->type != null) {
            $postvals['x_type'] = $this->type;
        }
        if ($this->card_code != null) {
            $postvals['x_card_code'] = $this->card_code;
        }
        if ($this->trans_id != null) {
            $postvals['x_trans_id'] = $this->trans_id;
        }
        if ($this->auth_code != null) {
            $postvals['x_auth_code'] = $this->auth_code;
        }

        // print_r($postvals);  // Debug output
        $html = "Input validated\n";

        /////////////////////////////////////
        // set_time_limit(0);
        $http=new http_class;
        $http->timeout=60;
        $http->data_timeout=60;
        $http->debug=0;
        $http->html_debug=1;

        $error=$http->GetRequestArguments($this->urlString, $arguments);
        $arguments["RequestMethod"]="POST";
        $arguments["PostValues"] = $postvals;
        $html .=  "Opening connection to:".HtmlEntities($arguments["HostName"])."\n";
        //flush();
        $html .="Ready to Xmit...\n";
        $error=$http->Open($arguments);
        if ($error)
            throw new Exception("Open failed.  Aborting.\n$error\n");
        $html .="Open succeeded\n";

        $error = $http->SendRequest($arguments);
        if ($error) {
            $http->Close();
            throw new Exception("SendRequest failed.  Aborting.\n$error\n");
        }
        $html .="SendRequest succeeded\n";

        $body = "";
        while (
            (!($error = $http->ReadReplyBody($buffer, AIM::MAX_RESPONSE_SIZE)))
            && strlen($buffer) > 0) {
            $html .="Read in " . strlen($buffer) . " response bytes\n";
            $body .= $buffer;
        }
        if ($error) {
            $http->Close();
            throw new Exception(
        "Failed while reading reply.  Aborting.\n$error\n\nReceived:\n$body\n");
        }
        $html .="ReadReplyBody succeeded (" . strlen($body) . " bytes total)\n";

        $html .="Response Body:  $body\n";  // Debug output
        $http->Close();

        $toker = new CSVTokenizer();
        $toker->setDelimiter($this->pdelimiter);
        $toker->setString($body);
        $i = 0;
        while (($token = $toker->nextToken()) !== false) {
            $this->data{$i} = $token;
            if ($this->pencap != "\0") {
                if (strlen($this->data{i}) < 2
                || $this->data{i}{0} != $this->pencap
                || $this->data{i}{strlen($this->data{i}) - 1} != $this->pencap)
                throw new Exception("Missing encap char for field " . (i+1));
                $this->data{i} = substring($this->data{i}, 1,
                        strlen($this->data{i}) - 2);
            }
            ++$i;
        }
        if ($i < $this->pminfields)
            throw new Exception("Field count under the minimum:  "
                    . $i . " < " . $this->pminfields);
    }

    public $data = array();

    public function toString() {
        if ($this->data == null) return null;
        $sb = "";
        for ($i = 0; $i < count($this->data); $i++)
        $sb .= (strval($i) . ": (" . $this->data{$i} . ")\n");
        return $sb;
    }

    public function getRRtext() { 
        return $this->data{3};
    }

    public function getApproval() { 
        return $this->data{4};
    }

    public function getTransType() { 
        return $this->data{11};
    }

    public function getAVS() { 
        if ($this->data{5} == null || strlen($this->data{5}) < 1) return "\0";
        return $this->data{5}{0};
    }

    public function getRRcode() { 
        return (int) $this->data{2};
    }

    public function getTransId() { 
        return (int) $this->data{6};
    }

    public function getAmount() { 
        return (int) ($this->data{9} * 100 + .5);
    }

    public function getRcode() { 
        return (int) ($this->data{0});
    }

    public function validateTypical() {
        11;
        if ($this->getRcode() != 1) throw new Exception($this->getRRtext());
        if ($this->getTransType() != "auth_capture")
        throw new Exception("Unexpected trans type: " . $this->getTransType());
        if ($this->requestedAmount > -1
                && $this->requestedAmount != $this->getAmount())
            throw new Exception("Asked for amount of "
            . " $this->requestedAmount, but was granted "
            . $this->getAmount() . '.');
    }

    /**
     * Template method for DB insert.
     * N.b., I have not converted this from Java!
     *
     * Caller must commit the transaction!
    public function storePaymentRecord(Connection con, int orderid, String vehicle) {
        PreparedStatement ps = con.prepareStatement(
            "INSERT INTO payment (\n"
            + "    ordernum, amount, approvalcode, transactionid, vehicle\n"
            + ") VALUES (?, ?, ?, ?, ?)");
        ps.setInt(1, orderid);
        ps.setInt(2, getAmount());
        ps.setString(3, getApproval());
        ps.setInt(4, getTransId());
        ps.setString(5, vehicle);
        int retval = ps.executeUpdate();
        if (retval != 1) throw new Exception(
                "Insert of payment record updated " + retval + " rows");
    }
     */ 

    /**
     * Verify that input data is good to submit a payment request.
     *
     * We assume that the addresses have already been validated.
     */ 
    public function validateInput($vehicle) {
        if ($this->email != null && !strpos($this->email, '@'))
            throw new Exception("Malformatted email address");
        if ($vehicle == null) throw new Exception("No card type selected");
        if ($this->exp_date == null) throw new Exception("No exp. date set");
        if ($this->card_code == null) throw new Exception("No card code set");
        if ($this->card_num == null) throw new Exception("No card number set");
        if (!pre_match("^\\d\\d/\\d\\d$", $this->exp_date))
            throw new Exception("Expiration date not of format MM/YY");
            if (!pre_match("^\\d\\d\\d\\d?$", $this->card_code))
            throw new Exception("Card Code not of format 888 or 8888");
            if (!pre_match("^\\d+$", $this->card_num))
            throw new Exception("Card number is non-numerical");
        if ($vehicle != "visa") {
            if ((strlen($this->card_num) != 16 && strlen($this->card_num) != 13)
            || $this->card_num{0} != '4')
                throw new Exception("Malformatted VISA card number");
        } else if ($vehicle == "mc") {
            if (strlen($this->card_num) != 16
            || $this->card_num{0} != '5')
                throw new Exception("Malformatted MasterCard card number");
        } else if ($vehicle == "amex") {
            if (strlen($this->card_num) != 15 || $this->card_num{0} != '3'
            || ($this->card_num{1} != '4'
            && $this->card_num{1} != '7'))
                throw new Exception(
                        "Malformatted American Express card number");
        } else if ($vehicle == "disc") {
            if (strlen($this->card_num) != 16 || !$this->card_num.startsWith("6011"))
                throw new Exception("Malformatted Discover card number");
        } else {
            throw new Exception("Unknown card type '" . $vehicle . "'");
        }
    }
}
?>
