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

    amazonSimplepath.select("button")[0].observe("click", function(e) {
        e.stop();
        switch (amazonSimplepath.select("select")[0].value) {
          case "new":
          case "retrieve":
            window.open(amazonSimplepathUrl);
            break;
          case "existing":
            amazonFields.show();
            amazonInstructions.show();
            amazonSimplepath.hide();
            break;
        }
    });

    amazonImportButton.select("button")[0].observe("click", function(e) {
        amazonImportButton.hide();
        amazonImport.show();
    });

  }


});
