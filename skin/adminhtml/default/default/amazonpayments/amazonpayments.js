// Amazon Payments Adminhtml

document.observe("dom:loaded", function() {
  if ($("payment_amazon_payments")) {
    var amazonSimplepath = $("amazon_simplepath");
    var amazonInstructions = $("amazon_instructions");
    var amazonFields = $("payment_amazon_payments").select("table")[0];
    var amazonImport = $("row_payment_credentials_simplepath_json");

    amazonInstructions.hide();
    amazonFields.hide();
    amazonImport.hide();

    amazonSimplepath.select("button")[0].observe("click", function(e) {
        e.stop();
        switch (amazonSimplepath.select("select")[0].value) {
          case "new":
          case "retrieve":
            window.open("https://sellercentral.amazon.com/", "amazon", "height=500,width=500");
            break;
          case "existing":
            amazonFields.show();
            amazonInstructions.show();
            amazonSimplepath.hide();
            break;
        }
    });

    $("row_payment_credentials_simplepath_import_button").select("button")[0].observe("click", function(e) {
        $("row_payment_credentials_simplepath_import_button").hide();
        amazonImport.show();
    });

  }


});
