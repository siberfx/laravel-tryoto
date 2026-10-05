<?php

return [

    'titles' => [
        'order_status' => ':icon :order numaralı sipariş: :status',
        'shipment_error' => '⚠️ :order numaralı siparişte gönderi hatası',
        'new_order' => '🆕 Yeni sipariş :order',
        'wallet_transaction' => '💳 Cüzdan işlemi :amount',
    ],

    'fields' => [
        'status' => 'Durum',
        'carrier_status' => 'Kargo firması durumu',
        'return_status' => 'İade durumu',
        'carrier' => 'Kargo firması',
        'tracking_number' => 'Takip numarası',
        'driver' => 'Kurye',
        'failed_attempt' => 'Başarısız teslimat denemesi',
        'note' => 'Not',
        'error' => 'Hata',
        'code' => 'Kod',
        'carrier_response' => 'Kargo firması yanıtı',
        'total' => 'Toplam',
        'payment' => 'Ödeme',
        'customer' => 'Müşteri',
        'sales_channel' => 'Satış kanalı',
        'brand' => 'Marka',
        'items' => 'Ürün sayısı',
        'order' => 'Sipariş',
        'type' => 'Tür',
        'charge_type' => 'Ücret türü',
        'description' => 'Açıklama',
        'remaining_balance' => 'Kalan bakiye',
    ],

    'links' => [
        'open' => 'Aç',
        'track_shipment' => 'Gönderiyi takip et',
    ],

    'boolean' => [
        'yes' => 'evet',
        'no' => 'hayır',
    ],

    // OTO sipariş durum kodları, mesaj başlıklarında gösterilir. Bilinmeyen kodlar olduğu gibi gösterilir.
    'statuses' => [
        'updated' => 'güncellendi',
        'new' => 'yeni',
        'assignedToWarehouse' => 'depoya atandı',
        'shipmentCreated' => 'gönderi oluşturuldu',
        'shipmentProcessing' => 'gönderi hazırlanıyor',
        'searchingDriver' => 'kurye aranıyor',
        'pickedUp' => 'teslim alındı',
        'shipmentInProgress' => 'gönderi sürüyor',
        'inTransit' => 'yolda',
        'outForDelivery' => 'dağıtımda',
        'delivered' => 'teslim edildi',
        'onHold' => 'beklemede',
        'shipmentOnHold' => 'gönderi beklemede',
        'canceled' => 'iptal edildi',
        'cancelled' => 'iptal edildi',
        'shipmentCanceled' => 'gönderi iptal edildi',
        'returned' => 'iade edildi',
        'returnShipmentProcessing' => 'iade hazırlanıyor',
        'reverseShipment' => 'iade gönderisi',
        'reverseShipmentCreated' => 'iade gönderisi oluşturuldu',
        'reverseShipmentProcessing' => 'iade gönderisi hazırlanıyor',
        'reverseShipmentOnHold' => 'iade gönderisi beklemede',
        'reverseShipmentCanceled' => 'iade gönderisi iptal edildi',
        'reverseReturned' => 'göndericiye iade edildi',
        'lost' => 'kayboldu',
        'damaged' => 'hasarlı',
    ],

];
