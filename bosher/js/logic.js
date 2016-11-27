var controller = {
    params: {
        block: 1,
        state: 1,
        productPosition: 0,
        assocPosition: 0
    },

    stateCounters: {
        1 : 'sectionCount',
        2 : 'productPosition',
        3 : 'assocPosition'
    },

    stepMax: 8,
    iter: 0,

    run : function() {
        var self = this;

        $("#main-btn").on("click", function(e) {
            e.preventDefault();

            $(this).html('Идет загрузка <i class="fa fa-circle-o-notch fa-spin"></i>');
            $(this).attr("disabled", "disabled");
            self.catalogImport();
        });
    },

    catalogImport : function() {
        var self = this;

        var scriptName = "import.php";
        if(self.params.block == 2) {
            scriptName = "import_accessories.php";
        }

        $.getJSON(scriptName, self.params, function(data) {
            var $stateBlock = $("#block_" + self.params.block + "_state_" + self.params.state);
            $stateBlock.find('.value').text(data[self.stateCounters[self.params.state]]);

            if(data.state != self.params.state) {
                $stateBlock.find(".status").removeClass("wait").addClass("success").html('<i class="fa fa-check"></i>');
                $stateBlock.removeClass('active');
                $("#state_" + data.state).addClass('active');
            }

            if(data.messages) {
                $.each(data.messages, function(key, message) {
                    $("#log").prepend('<div class="log-item">' + message + '</div>');
                })
            }

            $.each(self.params, function(index) {
                if(index != 'block') {
                    self.params[index] = data[index];
                }

            });

            if(self.params.state != 4) {
                self.iter++;
                self.catalogImport();
            } else {
                self.priceImport();
            }
        });
    },

    priceImport: function() {
        var self = this;

        var scriptName = "import_prices.php";
        if(self.params.block == 2) {
            scriptName = "import_prices_acs.php";
        } else if(self.params.block == 3) {
            scriptName = "import_prices_mt.php";
        }

        $.getJSON(scriptName, function(data) {

            var $stateBlock = $("#block_" + self.params.block + "_state_" + self.params.state);
            $stateBlock.find('.value').text(data['count']);

            $stateBlock.find(".status").removeClass("wait").addClass("success").html('<i class="fa fa-check"></i>');
            $stateBlock.removeClass('active');
            $("#state_" + data.state).addClass('active');

            if(self.params.block == 1) {
                self.params.block = 2;
                self.params.state = 1;
                self.params.productPosition = 0;
                self.params.assocPosition = 0;

                self.catalogImport();
            } else {
                if(self.params.block == 2 && self.params.state == 4)
                {
                    self.params.block = 3;
                    self.params.state = 4;
                    self.priceImport();
                } else {
                    self.params.block = 2;
                    self.params.state = 5;
                    self.accessoriesAssoc();
                }
            }
        });
    },

    accessoriesAssoc: function() {
        var self = this;

        $.getJSON("import_accessories_assoc.php", function(data) {
            var $stateBlock = $("#block_" + self.params.block + "_state_" + self.params.state);

            $stateBlock.find('.value').text(data['count']);

            $stateBlock.find(".status").removeClass("wait").addClass("success").html('<i class="fa fa-check"></i>');
            $stateBlock.removeClass('active');
            $("#state_" + data.state).addClass('active');

            $("#main-btn").html('Загрузка завершена <i class="fa fa-check"></i>');
        });
    }
};

$(function() {
    controller.run();
});
