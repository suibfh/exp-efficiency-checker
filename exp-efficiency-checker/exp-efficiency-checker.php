<?php
/**
 * Plugin Name: Exp Efficiency Checker
 * Description: 無色石（BPC）とダイヤ（USD）のどちらが効率よく経験値を得られるか計算するショートコード [exp_efficiency_checker]
 * Version:     1.2.1
 * Author:      あなたの名前
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * BPC→USD レートを取得（GeckoTerminal API版）
 */
function ee_fetch_bpc_price_usd() {
    $url = 'https://api.geckoterminal.com/api/v2/networks/oasys/pools/0x53d749ea2507182586b9795ad1435938473d448d';
    $res = wp_remote_get( $url, [ 'timeout' => 5 ] );
    if ( is_wp_error( $res ) ) {
        return false;
    }
    $data = json_decode( wp_remote_retrieve_body( $res ), true );
    if ( ! isset( $data['data']['attributes']['base_token_price_usd'] ) ) {
        return false;
    }
    return floatval( $data['data']['attributes']['base_token_price_usd'] );
}

/**
 * ショートコード本体
 */
function ee_shortcode_callback() {
    $bpc_usd = ee_fetch_bpc_price_usd();
    $bpc_usd_display = $bpc_usd !== false ? number_format( $bpc_usd, 5 ) : '取得失敗';

    ob_start();
    ?>
    <div id="ee-checker" style="border:1px solid #ddd; padding:1em; max-width:400px;">
      <p>
        <label>無色石購入個数&nbsp;
          <input type="number" id="ee_qty" placeholder="購入する石の個数を入力" step="1" style="width:120px;">
        </label>
      </p>
      <p>
        <label>購入合計 BPC&nbsp;
          <input type="number" id="ee_total_bpc" placeholder="合計で支払うBPC数を入力" step="0.01" style="width:120px;">
        </label>
      </p>
      <p>1個あたり BPC 価格: <span id="ee_bpc_per_stone">-</span> BPC</p>
      <p>BPC → USD レート：<span id="ee_bpc_usd"><?php echo esc_html( $bpc_usd_display ); ?></span> USD</p>
      <p>ダイヤ価格：0.01 USD/個</p>
      <p><button id="ee_calc">計算する</button></p>
      <div id="ee_result" style="margin-top:1em; font-weight:bold;"></div>
    </div>

    <script>
    (function(){
      const expStone = 4400;
      const expDia   = 4400;
      const pricePerDia = 0.01; // USD

      document.getElementById('ee_calc').addEventListener('click', function(){
        const qty       = parseFloat(document.getElementById('ee_qty').value) || 0;
        const totalBpc  = parseFloat(document.getElementById('ee_total_bpc').value) || 0;
        const bpcUsd    = parseFloat(document.getElementById('ee_bpc_usd').textContent) || 0;

        // 1個あたり BPC 価格を計算・表示
        const perStoneBpc = qty > 0 ? (totalBpc / qty).toFixed(4) : '0.0000';
        document.getElementById('ee_bpc_per_stone').textContent = perStoneBpc;

        // 無色石の USD 換算価格
        const stoneUsd = parseFloat(perStoneBpc) * bpcUsd;
        const rateStone = stoneUsd > 0 ? (expStone / stoneUsd).toFixed(2) : '0.00';
        const rateDia   = (expDia / pricePerDia).toFixed(2);

        let winner = '同等';
        if ( rateStone > rateDia ) { winner = '無色石'; }
        else if ( rateDia  > rateStone ) { winner = 'ダイヤ'; }

        document.getElementById('ee_result').innerHTML =
          `無色石：${rateStone} exp/USD<br>` +
          `ダイヤ：${rateDia} exp/USD<br>` +
          `<span style="color: #d33;">${winner} の方がお得です！</span>`;
      });
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'exp_efficiency_checker', 'ee_shortcode_callback' );
?>
