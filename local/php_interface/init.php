<?

require (dirname(__FILE__) . "/classes/DiscountPercent.php");

//События перед добавлением записи в корзину
AddEventHandler("sale", "OnBeforeBasketAdd", "MyOnBeforeBasketAdd");

function MyOnBeforeBasketAdd(&$arFields) {

    if (
        empty($arFields["DISCOUNT_PRICE"]) &&
        $arFields["PRODUCT_ID"] > 0
    ) {

        $discount = DiscountPercent::getByProductSkuID($arFields["PRODUCT_ID"]);
        $arFields["DISCOUNT_PRICE"] = roundEx($arFields["PRICE"] * $discount / 100.0, 2);
        $arFields["PRICE"] = $arFields["PRICE"] - $arFields["DISCOUNT_PRICE"];

    }

}

//События перед изменением записи в корзине
AddEventHandler("sale", "OnBeforeBasketUpdate", "MyOnBeforeBasketUpdate");

function MyOnBeforeBasketUpdate($ID, &$arFields) {

    if (
        empty($arFields["ORDER_ID"]) &&
        empty($arFields["DISCOUNT_PRICE"]) &&
        $arFields["PRODUCT_PRICE_ID"] > 0
    ) {

        $discount = DiscountPercent::getByPriceID($arFields["PRODUCT_PRICE_ID"]);
        $arFields["DISCOUNT_PRICE"] = roundEx($arFields["BASE_PRICE"] * $discount / 100.0, 2);
        $arFields["PRICE"] = $arFields["BASE_PRICE"] - $arFields["DISCOUNT_PRICE"];
        $arFields["DISCOUNT_VALUE"] = $discount . " %";

    }

}



