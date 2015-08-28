// Amazon Payments Adminhtml

// var amazonSimplepathUrl is defined in Amazon_Payments_Model_System_Config_Backend_Enabled->getCommentText()

document.observe("dom:loaded", function() {
  if ($("payment_amazon_payments")) {
    var amazonSimplepath = $("amazon_simplepath");
    var amazonInstructions = $("amazon_instructions");
    var amazonFields = $("payment_amazon_payments").select("table")[0];
    var amazonImport = $("row_payment_ap_credentials_simplepath_json");
    var amazonImportButton = $("row_payment_ap_credentials_simplepath_import_button");


    amazonInstructions.hide();
    amazonFields.hide();
    amazonImport.hide();


    var form = new Element('form', { method: 'post', action: amazonSimplepathUrl, id: 'simplepath_form', target: 'simplepath'});
    amazonSimplepath.wrap(form);

    $("simplepath_form").observe("submit", function(e) {
        // window.open('', 'simplepath', "height=500, width=500");
        e.stop();
        window.open(amazonSimplepathUrl, 'simplepath', "height=500, width=500");
    });


    amazonSimplepath.select("a")[0].observe("click", function(e) {
        e.stop();
        amazonFields.show();
        amazonInstructions.show();
        amazonSimplepath.hide();
    });

    amazonImportButton.select("button")[0].observe("click", function(e) {
        amazonImportButton.hide();
        amazonImport.show();
    });

    if (!amazonIsSecure) {
        $("amazon_https_required").show();
    }
    if (!amazonHasOpenssl) {
        $("amazon_openssl_required").show();
    }

  }


});
