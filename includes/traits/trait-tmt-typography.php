<?php
/**
 * TMT Typography Trait methods.
 *
 * @package Tibetan_Multilingual_Typography
 */

defined( 'ABSPATH' ) || exit;

trait TMT_Typography_Trait {
	/* ==========================================================================
	   MODULE B: Dynamic Typography Engine & Master Tibetan Font Registry
	   ========================================================================== */

	/**
	 * Master Tibetan Font Registry
	 *
	 * Categorized into Monlam Bodtig Series, Qomolang Series, Myilam Series,
	 * and Web & Open Standards.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_font_registry(): array {
		return [
			// ==========================================
			// Category 1: Monlam Bodtig Series (མོན་ལམ་བོད་ཡིག)
			// ==========================================
			'monlam-bodtig-2' => [
				'name'       => 'Monlam Bodtig Uchen 2 (མོན་ལམ་དབུ་ཅན་དཀྱུས་མ)',
				'family'     => "'Monlam Uni OuChan 2'",
				'local'      => [ 'Monlam Uni OuChan 2', 'MonlamUniOuChan2', 'Monlam Uni OuChan' ],
				'file'       => 'fonts/monlam/MonlamUniOuChan2.woff2',
				'format'     => 'woff2',
				'category'   => 'monlam',
				'category_l' => __( 'Monlam Bodtig Series (མོན་ལམ་བོད་ཡིག)', 'omnitibet' ),
				'stack'      => "'Monlam Uni OuChan 2', 'Monlam Uni OuChan', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'monlam-bodtig-1' => [
				'name'       => 'Monlam Bodtig 1 Fine (མོན་ལམ་ཡིག་གཟུགས་ཕྲ་མོ)',
				'family'     => "'Monlam Uni OuChan 1'",
				'local'      => [ 'Monlam Uni OuChan 1', 'MonlamUniOuChan1' ],
				'file'       => 'fonts/monlam/monlam-uni-ouchan1.woff2',
				'format'     => 'woff2',
				'category'   => 'monlam',
				'category_l' => __( 'Monlam Bodtig Series (མོན་ལམ་བོད་ཡིག)', 'omnitibet' ),
				'stack'      => "'Monlam Uni OuChan 1', 'Monlam Uni OuChan', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'monlam-bodtig-3' => [
				'name'       => 'Monlam Bodtig 3 Bold (མོན་ལམ་སྦོམ་པོ)',
				'family'     => "'Monlam Uni OuChan 3'",
				'local'      => [ 'Monlam Uni OuChan 3', 'MonlamUniOuChan3' ],
				'file'       => 'fonts/monlam/monlam-uni-ouchan3.woff2',
				'format'     => 'woff2',
				'category'   => 'monlam',
				'category_l' => __( 'Monlam Bodtig Series (མོན་ལམ་བོད་ཡིག)', 'omnitibet' ),
				'stack'      => "'Monlam Uni OuChan 3', 'Monlam Uni OuChan', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'monlam-bodtig-5' => [
				'name'       => 'Monlam Bodtig 5 Tsugring / Title (མོན་ལམ་ཚུགས་རིང)',
				'family'     => "'Monlam Uni OuChan 5'",
				'local'      => [ 'Monlam Uni OuChan 5', 'MonlamUniOuChan5' ],
				'file'       => 'fonts/monlam/MonlamUniOuChan5.woff2',
				'format'     => 'woff2',
				'category'   => 'monlam',
				'category_l' => __( 'Monlam Bodtig Series (མོན་ལམ་བོད་ཡིག)', 'omnitibet' ),
				'stack'      => "'Monlam Uni OuChan 5', 'Monlam Uni OuChan', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],

			// ==========================================
			// Category 2: Qomolang Series (ཇོ་མོ་གླང་མའི་ཡིག་གཟུགས)
			// ==========================================
			'qomolang-title' => [
				'name'       => 'Qomolangma Title (ཇོ་མོ་གླང་མ་ཁ་བྱང་ཆེན་མོ)',
				'family'     => "'Qomolangma-Title'",
				'local'      => [ 'Qomolangma-Title', 'Qomolangma Title' ],
				'file'       => 'fonts/qomolang/Qomolangma-Title.woff2',
				'format'     => 'woff2',
				'category'   => 'qomolang',
				'category_l' => __( 'Qomolang Series (ཇོ་མོ་གླང་མའི་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'Qomolangma-Title', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'qomolang-subtitle' => [
				'name'       => 'Qomolangma Subtitle (ཇོ་མོ་གླང་མ་ཁ་བྱང་ཕྲ་མོ)',
				'family'     => "'Qomolangma-Subtitle'",
				'local'      => [ 'Qomolangma-Subtitle', 'Qomolangma Subtitle' ],
				'file'       => 'fonts/qomolang/Qomolangma-Subtitle.woff2',
				'format'     => 'woff2',
				'category'   => 'qomolang',
				'category_l' => __( 'Qomolang Series (ཇོ་མོ་གླང་མའི་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'Qomolangma-Subtitle', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'qomolang-drutsa' => [
				'name'       => 'Qomolangma Drutsa (ཇོ་མོ་གླང་མ་འབྲུ་ཚ)',
				'family'     => "'Qomolangma-Drutsa'",
				'local'      => [ 'Qomolangma-Drutsa', 'Qomolangma Drutsa' ],
				'file'       => 'fonts/qomolang/Qomolangma-Drutsa.woff2',
				'format'     => 'woff2',
				'category'   => 'qomolang',
				'category_l' => __( 'Qomolang Series (ཇོ་མོ་གླང་མའི་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'Qomolangma-Drutsa', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'qomolang-chuyig' => [
				'name'       => 'Qomolangma Chuyig Cursive (ཇོ་མོ་གླང་མ་འཁྱུག་ཡིག)',
				'family'     => "'Qomolangma-Chuyig'",
				'local'      => [ 'Qomolangma-Chuyig', 'Qomolangma Chuyig' ],
				'file'       => 'fonts/qomolang/Qomolangma-Chuyig.woff2',
				'format'     => 'woff2',
				'category'   => 'qomolang',
				'category_l' => __( 'Qomolang Series (ཇོ་མོ་གླང་མའི་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'Qomolangma-Chuyig', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', cursive, serif",
			],
			'qomolang-betsu' => [
				'name'       => 'Qomolangma Betsu (ཇོ་མོ་གླང་མ་དཔེ་ཚུགས)',
				'family'     => "'Qomolangma-Betsu'",
				'local'      => [ 'Qomolangma-Betsu', 'Qomolangma Betsu' ],
				'file'       => 'fonts/qomolang/Qomolangma-Betsu.woff2',
				'format'     => 'woff2',
				'category'   => 'qomolang',
				'category_l' => __( 'Qomolang Series (ཇོ་མོ་གླང་མའི་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'Qomolangma-Betsu', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'qomolang-tsuring' => [
				'name'       => 'Qomolangma Tsuring (ཇོ་མོ་གླང་མ་ཚུགས་རིང)',
				'family'     => "'Qomolangma-Tsuring'",
				'local'      => [ 'Qomolangma-Tsuring', 'Qomolangma Tsuring' ],
				'file'       => 'fonts/qomolang/Qomolangma-Tsuring.woff2',
				'format'     => 'woff2',
				'category'   => 'qomolang',
				'category_l' => __( 'Qomolang Series (ཇོ་མོ་གླང་མའི་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'Qomolangma-Tsuring', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'qomolang-tsutong' => [
				'name'       => 'Qomolangma Tsutong (ཇོ་མོ་གླང་མ་ཚུགས་ཐུང)',
				'family'     => "'Qomolangma-Tsutong'",
				'local'      => [ 'Qomolangma-Tsutong', 'Qomolangma Tsutong' ],
				'file'       => 'fonts/qomolang/Qomolangma-Tsutong.woff2',
				'format'     => 'woff2',
				'category'   => 'qomolang',
				'category_l' => __( 'Qomolang Series (ཇོ་མོ་གླང་མའི་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'Qomolangma-Tsutong', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'qomolang-tsumachu' => [
				'name'       => 'Qomolangma Tsumachu (ཇོ་མོ་གླང་མ་ཚུགས་མ་འཁྱུག)',
				'family'     => "'Qomolangma-Tsumachu'",
				'local'      => [ 'Qomolangma-Tsumachu', 'Qomolangma Tsumachu' ],
				'file'       => 'fonts/qomolang/Qomolangma-Tsumachu.woff2',
				'format'     => 'woff2',
				'category'   => 'qomolang',
				'category_l' => __( 'Qomolang Series (ཇོ་མོ་གླང་མའི་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'Qomolangma-Tsumachu', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'qomolang-uchen-sarchen' => [
				'name'       => 'Qomolangma Uchen Sarchen (ཇོ་མོ་གླང་མ་དབུ་ཅན་གསར་ཆེན)',
				'family'     => "'Qomolangma-UchenSarchen'",
				'local'      => [ 'Qomolangma-UchenSarchen', 'Qomolangma Uchen Sarchen' ],
				'file'       => 'fonts/qomolang/Qomolangma-UchenSarchen.woff2',
				'format'     => 'woff2',
				'category'   => 'qomolang',
				'category_l' => __( 'Qomolang Series (ཇོ་མོ་གླང་མའི་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'Qomolangma-UchenSarchen', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'qomolang-uchen-sarchung' => [
				'name'       => 'Qomolangma Uchen Sarchung (ཇོ་མོ་གླང་མ་དབུ་ཅན་གསར་ཆུང)',
				'family'     => "'Qomolangma-UchenSarchung'",
				'local'      => [ 'Qomolangma-UchenSarchung', 'Qomolangma Uchen Sarchung' ],
				'file'       => 'fonts/qomolang/Qomolangma-UchenSarchung.woff2',
				'format'     => 'woff2',
				'category'   => 'qomolang',
				'category_l' => __( 'Qomolang Series (ཇོ་མོ་གླང་མའི་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'Qomolangma-UchenSarchung', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'qomolang-uchen-suring' => [
				'name'       => 'Qomolangma Uchen Suring (ཇོ་མོ་གླང་མ་དབུ་ཅན་ཚུགས་རིང)',
				'family'     => "'Qomolangma-UchenSuring'",
				'local'      => [ 'Qomolangma-UchenSuring', 'Qomolangma Uchen Suring' ],
				'file'       => 'fonts/qomolang/Qomolangma-UchenSuring.woff2',
				'format'     => 'woff2',
				'category'   => 'qomolang',
				'category_l' => __( 'Qomolang Series (ཇོ་མོ་གླང་མའི་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'Qomolangma-UchenSuring', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'qomolang-uchen-sutung' => [
				'name'       => 'Qomolangma Uchen Sutung (ཇོ་མོ་གླང་མ་དབུ་ཅན་ཚུགས་ཐུང)',
				'family'     => "'Qomolangma-UchenSutung'",
				'local'      => [ 'Qomolangma-UchenSutung', 'Qomolangma Uchen Sutung' ],
				'file'       => 'fonts/qomolang/Qomolangma-UchenSutung.woff2',
				'format'     => 'woff2',
				'category'   => 'qomolang',
				'category_l' => __( 'Qomolang Series (ཇོ་མོ་གླང་མའི་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'Qomolangma-UchenSutung', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'qomolang-woodblock' => [
				'name'       => 'Qomolangma Woodblock (ཇོ་མོ་གླང་མ་ཤིང་པར)',
				'family'     => "'Qomolangma-Woodblock'",
				'local'      => [ 'Qomolangma-Woodblock', 'Qomolangma Woodblock' ],
				'file'       => 'fonts/qomolang/Qomolangma-Woodblock.woff2',
				'format'     => 'woff2',
				'category'   => 'qomolang',
				'category_l' => __( 'Qomolang Series (ཇོ་མོ་གླང་མའི་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'Qomolangma-Woodblock', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'qomolang-dunhuang' => [
				'name'       => 'Qomolangma Dunhuang (ཇོ་མོ་གླང་མ་ཏུན་ཧོང་)',
				'family'     => "'Qomolangma-Dunhuang'",
				'local'      => [ 'Qomolangma-Dunhuang', 'Qomolangma Dunhuang' ],
				'file'       => 'fonts/qomolang/Qomolangma-Dunhuang.woff2',
				'format'     => 'woff2',
				'category'   => 'qomolang',
				'category_l' => __( 'Qomolang Series (ཇོ་མོ་གླང་མའི་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'Qomolangma-Dunhuang', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'qomolang-edict' => [
				'name'       => 'Qomolangma Edict (ཇོ་མོ་གླང་མ་བཀའ་གཙིགས)',
				'family'     => "'Qomolangma-Edict'",
				'local'      => [ 'Qomolangma-Edict', 'Qomolangma Edict' ],
				'file'       => 'fonts/qomolang/Qomolangma-Edict.woff2',
				'format'     => 'woff2',
				'category'   => 'qomolang',
				'category_l' => __( 'Qomolang Series (ཇོ་མོ་གླང་མའི་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'Qomolangma-Edict', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'qomolang-art' => [
				'name'       => 'Qomolangma Art (ཇོ་མོ་གླང་མ་མཛེས་རྩལ)',
				'family'     => "'Qomolangma-Art'",
				'local'      => [ 'Qomolangma-Art', 'Qomolangma Art' ],
				'file'       => 'fonts/qomolang/Qomolangma-Art.woff2',
				'format'     => 'woff2',
				'category'   => 'qomolang',
				'category_l' => __( 'Qomolang Series (ཇོ་མོ་གླང་མའི་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'Qomolangma-Art', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'qomolang-horyig' => [
				'name'       => 'Qomolangma Horyig (ཇོ་མོ་གླང་མ་ཧོར་ཡིག)',
				'family'     => "'Qomolangma-Horyig'",
				'local'      => [ 'Qomolangma-Horyig', 'Qomolangma Horyig' ],
				'file'       => 'fonts/qomolang/Qomolangma-Horyig.woff2',
				'format'     => 'woff2',
				'category'   => 'qomolang',
				'category_l' => __( 'Qomolang Series (ཇོ་མོ་གླང་མའི་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'Qomolangma-Horyig', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],

			// ==========================================
			// Category 3: Myilam Series (རྨི་ལམ་ཡིག་གཟུགས)
			// ==========================================
			'myilam-boldface' => [
				'name'       => 'Myilam Boldface (རྨི་ལམ་སྦོམ་ཡིག)',
				'family'     => "'MYILAM-BOLDFACE'",
				'local'      => [ 'MYILAM BOLDFACE', 'MYILAM_BOLDFACE' ],
				'file'       => 'fonts/myilam/MYILAM_BOLDFACE.woff2',
				'format'     => 'woff2',
				'category'   => 'myilam',
				'category_l' => __( 'Myilam Series (རྨི་ལམ་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'MYILAM-BOLDFACE', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', sans-serif, serif",
			],
			'myilam-lightface' => [
				'name'       => 'Myilam Lightface (རྨི་ལམ་ཕྲ་ཡིག)',
				'family'     => "'MYILAM-LIGHTFACE'",
				'local'      => [ 'MYILAM LIGHTFACE', 'MYILAM_LIGHTFACE' ],
				'file'       => 'fonts/myilam/MYILAM_LIGHTFACE.woff2',
				'format'     => 'woff2',
				'category'   => 'myilam',
				'category_l' => __( 'Myilam Series (རྨི་ལམ་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'MYILAM-LIGHTFACE', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'myilam-carton' => [
				'name'       => 'Myilam Cartoon (རྨི་ལམ་བྱིས་སྒྲུང་)',
				'family'     => "'MYILAM-CARTON'",
				'local'      => [ 'MYILAM CARTON', 'MYILAM_CARTON' ],
				'file'       => 'fonts/myilam/MYILAM_CARTON.woff2',
				'format'     => 'woff2',
				'category'   => 'myilam',
				'category_l' => __( 'Myilam Series (རྨི་ལམ་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'MYILAM-CARTON', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'myilam-gothic' => [
				'name'       => 'Myilam Gothic (རྨི་ལམ་གོ་ཐིག)',
				'family'     => "'MYILAM-GOTHIC'",
				'local'      => [ 'MYILAM GOTHIC', 'MYILAM_GOTHIC' ],
				'file'       => 'fonts/myilam/MYILAM_GOTHIC.woff2',
				'format'     => 'woff2',
				'category'   => 'myilam',
				'category_l' => __( 'Myilam Series (རྨི་ལམ་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'MYILAM-GOTHIC', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', sans-serif",
			],
			'myilam-green-leaf' => [
				'name'       => 'Myilam Green Leaf (རྨི་ལམ་ལོ་ལྗང)',
				'family'     => "'MYILAM-GREEN-LEAF'",
				'local'      => [ 'MYILAM GREEN LEAF', 'MYILAM_GREEN LEAF' ],
				'file'       => 'fonts/myilam/myilam-green-leaf.woff2',
				'format'     => 'woff2',
				'category'   => 'myilam',
				'category_l' => __( 'Myilam Series (རྨི་ལམ་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'MYILAM-GREEN-LEAF', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'myilam-khyungdri' => [
				'name'       => 'Myilam Khyungdri (རྨི་ལམ་ཁྱུང་བྲིས)',
				'family'     => "'MYILAM-KHYUNGDRI'",
				'local'      => [ 'MYILAM KHYUNGDRI', 'MYILAM_KHYUNGDRI' ],
				'file'       => 'fonts/myilam/MYILAM_KHYUNGDRI.woff2',
				'format'     => 'woff2',
				'category'   => 'myilam',
				'category_l' => __( 'Myilam Series (རྨི་ལམ་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'MYILAM-KHYUNGDRI', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'myilam-mandate' => [
				'name'       => 'Myilam Mandate (རྨི་ལམ་བཀའ་ཡིག)',
				'family'     => "'MYILAM-MANDATE'",
				'local'      => [ 'MYILAM MANDATE', 'MYILAM_MANDATE' ],
				'file'       => 'fonts/myilam/MYILAM_MANDATE.woff2',
				'format'     => 'woff2',
				'category'   => 'myilam',
				'category_l' => __( 'Myilam Series (རྨི་ལམ་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'MYILAM-MANDATE', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'myilam-masculine' => [
				'name'       => 'Myilam Masculine (རྨི་ལམ་ཕོ་ཡིག)',
				'family'     => "'MYILAM-MASCULINE'",
				'local'      => [ 'MYILAM MASCULINE', 'MYILAM_MASCULINE' ],
				'file'       => 'fonts/myilam/MYILAM_MASCULINE.woff2',
				'format'     => 'woff2',
				'category'   => 'myilam',
				'category_l' => __( 'Myilam Series (རྨི་ལམ་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'MYILAM-MASCULINE', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'myilam-feminine' => [
				'name'       => 'Myilam Feminine (རྨི་ལམ་མོ་ཡིག)',
				'family'     => "'MYILAM-FEMININE'",
				'local'      => [ 'MYILAM FEMININE', 'MYILAM_FEMININE' ],
				'file'       => 'fonts/myilam/MYILAM_FEMININE.woff2',
				'format'     => 'woff2',
				'category'   => 'myilam',
				'category_l' => __( 'Myilam Series (རྨི་ལམ་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'MYILAM-FEMININE', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'myilam-pearl-necklace' => [
				'name'       => 'Myilam Pearl Necklace (རྨི་ལམ་མུ་ཏིག་དོ་ཤལ)',
				'family'     => "'MYILAM-PEARL-NECKLACE'",
				'local'      => [ 'MYILAM PEARL NECKLACE', 'MYILAM_PEARL NECKLACE' ],
				'file'       => 'fonts/myilam/myilam-pearl-necklace.woff2',
				'format'     => 'woff2',
				'category'   => 'myilam',
				'category_l' => __( 'Myilam Series (རྨི་ལམ་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'MYILAM-PEARL-NECKLACE', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'myilam-horma' => [
				'name'       => 'Myilam Horma (རྨི་ལམ་ཧོར་མ)',
				'family'     => "'MYILAM-HORMA'",
				'local'      => [ 'MYILAM HORMA', 'MYILAM_HORMA' ],
				'file'       => 'fonts/myilam/MYILAM_HORMA.woff2',
				'format'     => 'woff2',
				'category'   => 'myilam',
				'category_l' => __( 'Myilam Series (རྨི་ལམ་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'MYILAM-HORMA', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],

			// ==========================================
			// Category 4: Web & Standards (Google Fonts & System)
			// ==========================================
			'noto-serif' => [
				'name'       => 'Noto Serif Tibetan (Google Fonts - གྷུ་གྷུལ་སེརིཕ)',
				'family'     => "'Noto Serif Tibetan'",
				'local'      => [ 'Noto Serif Tibetan' ],
				'file'       => '',
				'google'     => 'https://fonts.googleapis.com/css2?family=Noto+Serif+Tibetan:wght@400;700&display=swap',
				'category'   => 'google_web',
				'category_l' => __( 'Web & Open Standards (དྲ་ཐོག་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'Noto Serif Tibetan', 'Jomolhari', 'Monlam Uni OuChan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'noto-sans' => [
				'name'       => 'Noto Sans Tibetan (Google Fonts - གྷུ་གྷུལ་སཱནས)',
				'family'     => "'Noto Sans Tibetan'",
				'local'      => [ 'Noto Sans Tibetan' ],
				'file'       => '',
				'google'     => 'https://fonts.googleapis.com/css2?family=Noto+Sans+Tibetan:wght@400;700&display=swap',
				'category'   => 'google_web',
				'category_l' => __( 'Web & Open Standards (དྲ་ཐོག་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'Noto Sans Tibetan', 'Tibetan Machine Uni', 'Microsoft Himalaya', 'Kailash', sans-serif",
			],
			'jomolhari' => [
				'name'       => 'Jomolhari (OpenType Classic - ཇོ་མོ་གླང་མའི་ཡིག་ཆུང)',
				'family'     => "'Jomolhari'",
				'local'      => [ 'Jomolhari' ],
				'file'       => '',
				'category'   => 'google_web',
				'category_l' => __( 'Web & Open Standards (དྲ་ཐོག་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "'Jomolhari', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
			'custom' => [
				'name'       => 'Custom / Theme Default (OS Native Fallbacks)',
				'family'     => "inherit",
				'local'      => [],
				'file'       => '',
				'category'   => 'google_web',
				'category_l' => __( 'Web & Open Standards (དྲ་ཐོག་ཡིག་གཟུགས)', 'omnitibet' ),
				'stack'      => "inherit, 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif",
			],
		];
	}

	/**
	 * Configurable HTML Elements Definition
	 *
	 * Allows distinct font family and line-height allocation for individual HTML elements.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_configurable_elements(): array {
		return [
			'body' => [
				'label'        => __( 'Body Text & Paragraphs (གཞུང་དངོས)', 'omnitibet' ),
				'desc'         => __( 'Main article content, paragraphs, lists, and standard text elements.', 'omnitibet' ),
				'icon'         => 'dashicons-editor-paragraph',
				'tags'         => [ 'body', 'p', 'li', 'article' ],
				'selectors'    => 'body.tb-lang-bo, body.tb-lang-bo p, body.tb-lang-bo li, body.tb-lang-bo .entry-content p, body.tb-lang-bo .wp-block-paragraph, .tb-tibetan-text, [lang="bo"], [lang="bo-CN"], [lang="bo-IN"]',
				'default_font' => 'monlam-bodtig-2',
				'default_lh'   => 2.3,
				'preview_text' => '༄༅། །འཛམ་གླིང་ཞི་བདེ་དང་མཐུན་སྒྲིལ་གྱི་དོན་དུ་བོད་ཀྱི་རིག་གཞུང་དང་སྐད་ཡིག་འདི་ཉིད་གལ་ཆེན་པོ་ཡིན། འདིར་ཡིག་གཟུགས་དང་ཐིག་ཕྲེང་བར་སྟོང་གི་མཛེས་ཆ་ལྟ་ཐུབ། ཡི་གེ་སྟེང་འོག་བརྩེགས་མ་རྣམས་མི་ཆད་པར་གསལ་པོར་ཐོན་ཐུབ།',
			],
			'h1_h2' => [
				'label'        => __( 'Main Headings - H1 & H2 (ཁ་བྱང་ཆེན་མོ)', 'omnitibet' ),
				'desc'         => __( 'Document post titles, main page headers, and top-level article titles.', 'omnitibet' ),
				'icon'         => 'dashicons-heading',
				'tags'         => [ 'h1', 'h2', '.entry-title' ],
				'selectors'    => 'body.tb-lang-bo h1, body.tb-lang-bo h2, body.tb-lang-bo .entry-title, body.tb-lang-bo .page-title, body.tb-lang-bo .wp-block-post-title, body.tb-lang-bo h1.wp-block-heading, body.tb-lang-bo h2.wp-block-heading',
				'default_font' => 'qomolang-title',
				'default_lh'   => 2.1,
				'preview_text' => 'བཀྲ་ཤིས་བདེ་ལེགས། དེང་རབས་བོད་ཡིག་དྲ་ཚིགས།',
			],
			'h3_h6' => [
				'label'        => __( 'Sub-Headings - H3 to H6 (ཁ་བྱང་ཆུང་བ)', 'omnitibet' ),
				'desc'         => __( 'Section headlines, sidebar widget titles, and subheadings.', 'omnitibet' ),
				'icon'         => 'dashicons-editor-italic',
				'tags'         => [ 'h3', 'h4', 'h5', 'h6' ],
				'selectors'    => 'body.tb-lang-bo h3, body.tb-lang-bo h4, body.tb-lang-bo h5, body.tb-lang-bo h6, body.tb-lang-bo h3.wp-block-heading, body.tb-lang-bo h4.wp-block-heading',
				'default_font' => 'monlam-bodtig-3',
				'default_lh'   => 2.2,
				'preview_text' => 'ས་བཅད་གསུམ་པ། སྐད་ཡིག་ཉམས་གསོ་དང་དར་སྤེལ།',
			],
			'nav' => [
				'label'        => __( 'Navigation Menus & Links (དཀར་ཆག་དང་འབྲེལ་མཐུད)', 'omnitibet' ),
				'desc'         => __( 'Header navigation bar, menu items, header buttons, and links.', 'omnitibet' ),
				'icon'         => 'dashicons-menu-alt3',
				'tags'         => [ 'nav', '.menu', 'header a' ],
				'selectors'    => 'body.tb-lang-bo nav, body.tb-lang-bo .nav-menu, body.tb-lang-bo .menu, body.tb-lang-bo .wp-block-navigation, body.tb-lang-bo .main-navigation a, body.tb-lang-bo header a, body.tb-lang-bo .wp-block-navigation-item a',
				'default_font' => 'myilam-boldface',
				'default_lh'   => 2.0,
				'preview_text' => 'གཙོ་ངོས། ｜ གནས་ཚུལ། ｜ རིག་གཞུང་། ｜ ང་ཚོའི་སྐོར།',
			],
			'quotes' => [
				'label'        => __( 'Blockquotes & Pullquotes (ལུང་འདྲེན་ཚིགས་བཅད)', 'omnitibet' ),
				'desc'         => __( 'Blockquotes, pullquotes, callouts, citations, and traditional verses.', 'omnitibet' ),
				'icon'         => 'dashicons-editor-quote',
				'tags'         => [ 'blockquote', 'cite', 'q' ],
				'selectors'    => 'body.tb-lang-bo blockquote, body.tb-lang-bo .wp-block-quote, body.tb-lang-bo .wp-block-pullquote, body.tb-lang-bo q, body.tb-lang-bo cite',
				'default_font' => 'qomolang-drutsa',
				'default_lh'   => 2.4,
				'preview_text' => '« ཤེས་རབ་ཀྱི་ཕ་རོལ་ཏུ་ཕྱིན་པ་ནི་བླ་ན་མེད་པའི་ཡེ་ཤེས་ཡིན། »',
			],
			'site_title' => [
				'label'        => __( 'Site Title & Branding (དྲ་ཚིགས་ཀྱི་མཚན་བྱང་)', 'omnitibet' ),
				'desc'         => __( 'Top site brand title in website banner and logo text.', 'omnitibet' ),
				'icon'         => 'dashicons-admin-site-alt3',
				'tags'         => [ '.site-title', 'brand' ],
				'selectors'    => 'body.tb-lang-bo .site-title, body.tb-lang-bo .site-title a, body.tb-lang-bo .wp-block-site-title, body.tb-lang-bo .wp-block-site-title a, body.tb-lang-bo .custom-logo-link',
				'default_font' => 'myilam-pearl-necklace',
				'default_lh'   => 2.0,
				'preview_text' => 'བོད་ཀྱི་དྲ་བའི་གནས་མཆོག',
			],
		];
	}

	/**
	 * Build Safe URL for Local Font File (Handling spaces in filenames)
	 *
	 * @param string $relative_path Relative path from plugin root.
	 * @return string
	 */
	public function get_font_file_url( string $relative_path ): string {
		$segments = explode( '/', $relative_path );
		$encoded  = array_map( 'rawurlencode', $segments );
		return TMT_PLUGIN_URL . implode( '/', $encoded );
	}

	/**
	 * Generate CSS @font-face Blocks for Given Font Keys
	 *
	 * @param array<string> $font_keys Array of registry keys.
	 * @return string
	 */
	public function generate_font_faces_css( array $font_keys ): string {
		$registry = $this->get_font_registry();
		$css      = '';
		$done     = [];

		foreach ( $font_keys as $key ) {
			if ( isset( $done[ $key ] ) || ! isset( $registry[ $key ] ) ) {
				continue;
			}
			$done[ $key ] = true;
			$info = $registry[ $key ];

			// Skip fonts without local files (e.g. Google Fonts / custom)
			if ( empty( $info['file'] ) ) {
				continue;
			}

			$font_path = TMT_PLUGIN_DIR . $info['file'];
			if ( ! file_exists( $font_path ) ) {
				continue;
			}

			$font_url = $this->get_font_file_url( $info['file'] );
			$format   = $info['format'] ?? 'woff2';
			$family   = $info['family'];

			$src_parts = [];
			if ( ! empty( $info['local'] ) && is_array( $info['local'] ) ) {
				foreach ( $info['local'] as $local_name ) {
					$src_parts[] = "local('" . esc_attr( $local_name ) . "')";
				}
			}
			$src_parts[] = "url('" . esc_url( $font_url ) . "') format('" . esc_attr( $format ) . "')";
			$src_string  = implode( ', ', $src_parts );

			$css .= "@font-face {\n";
			$css .= "  font-family: {$family};\n";
			$css .= "  src: {$src_string};\n";
			$css .= "  font-weight: normal;\n";
			$css .= "  font-style: normal;\n";
			$css .= "  font-display: swap;\n";
			$css .= "}\n";
		}

		return $css;
	}

	/**
	 * Render Categorized Font <optgroup> Options HTML
	 *
	 * @param string $selected_key Currently selected font key.
	 * @return string HTML <optgroup> tags.
	 */
	public function render_font_options_html( string $selected_key ): string {
		$registry   = $this->get_font_registry();
		$categories = [
			'monlam'     => __( 'Monlam Bodtig Series (མོན་ལམ་བོད་ཡིག)', 'omnitibet' ),
			'qomolang'   => __( 'Qomolang Series (ཇོ་མོ་གླང་མའི་ཡིག་གཟུགས)', 'omnitibet' ),
			'myilam'     => __( 'Myilam Series (རྨི་ལམ་ཡིག་གཟུགས)', 'omnitibet' ),
			'google_web' => __( 'Web & Open Standards (དྲ་ཐོག་ཡིག་གཟུགས)', 'omnitibet' ),
		];

		$html = '';
		foreach ( $categories as $cat_key => $cat_label ) {
			$html .= '<optgroup label="' . esc_attr( $cat_label ) . '">';
			foreach ( $registry as $font_key => $font_info ) {
				if ( $font_info['category'] === $cat_key ) {
					$selected = selected( $selected_key, $font_key, false );
					$html .= '<option value="' . esc_attr( $font_key ) . '"' . $selected . '>';
					$html .= esc_html( $font_info['name'] );
					$html .= '</option>';
				}
			}
			$html .= '</optgroup>';
		}
		return $html;
	}

	/**
	 * Inject Font-Faces for Admin Live Previews
	 */
	public function inject_admin_preview_font_faces(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'settings_page_tibetan-settings' !== $screen->id ) {
			return;
		}

		$registry       = $this->get_font_registry();
		$all_keys       = array_keys( $registry );
		$font_faces_css = $this->generate_font_faces_css( $all_keys );
		wp_add_inline_style( 'tmt-admin-css', $font_faces_css );
	}

	/**
	 * Enqueue Frontend Assets
	 */
	public function enqueue_frontend_assets(): void {
		$settings = $this->get_settings();
		$lang     = $this->get_current_language();

		wp_enqueue_style( 'tmt-frontend-css', TMT_PLUGIN_URL . 'assets/css/tibetan-frontend.css', [], TMT_VERSION );
		wp_enqueue_script( 'tmt-switcher-js', TMT_PLUGIN_URL . 'assets/js/tibetan-switcher.js', [], TMT_VERSION, true );

		if ( 'bo' === $lang ) {
			$registry    = $this->get_font_registry();
			$use_elem    = ! empty( $settings['enable_element_typography'] );
			$active_keys = [];

			if ( $use_elem ) {
				$elements = $this->get_configurable_elements();
				foreach ( array_keys( $elements ) as $elem_key ) {
					$f_key = $settings[ "font_{$elem_key}" ] ?? $elements[ $elem_key ]['default_font'];
					if ( ! empty( $f_key ) ) {
						$active_keys[] = $f_key;
					}
				}
			} else {
				$active_keys[] = $settings['font_family'] ?? 'monlam-bodtig-2';
			}

			foreach ( array_unique( $active_keys ) as $key ) {
				if ( ! empty( $registry[ $key ]['google'] ) ) {
					wp_enqueue_style( 'tmt-google-' . sanitize_key( $key ), $registry[ $key ]['google'], [], null );
				}
			}
		}
	}

	/**
	 * Inject Dynamic Typography CSS into Head for Tibetan Pages
	 */
	public function inject_dynamic_typography_css(): void {
		if ( 'bo' !== $this->get_current_language() ) {
			return;
		}

		$settings = $this->get_settings();
		$elements = $this->get_configurable_elements();
		$use_elem = ! empty( $settings['enable_element_typography'] );

		// Gather active font keys
		$active_font_keys = [];
		if ( $use_elem ) {
			foreach ( array_keys( $elements ) as $elem_key ) {
				$f_key = $settings[ "font_{$elem_key}" ] ?? $elements[ $elem_key ]['default_font'];
				if ( ! empty( $f_key ) ) {
					$active_font_keys[] = $f_key;
				}
			}
		} else {
			$active_font_keys[] = $settings['font_family'] ?? 'monlam-bodtig-2';
		}
		$active_font_keys = array_unique( $active_font_keys );

		// Generate @font-face for active local fonts
		$css = $this->generate_font_faces_css( $active_font_keys );

		if ( $use_elem ) {
			foreach ( $elements as $elem_key => $elem_info ) {
				$f_key     = $settings[ "font_{$elem_key}" ] ?? $elem_info['default_font'];
				$stack     = $this->get_font_stack( $f_key );
				$lh        = floatval( $settings[ "lh_{$elem_key}" ] ?? $elem_info['default_lh'] );
				if ( $lh < 1.5 || $lh > 4.0 ) {
					$lh = $elem_info['default_lh'];
				}
				$selectors = $elem_info['selectors'];
				$css .= $selectors . " {\n";
				$css .= 'font-family: ' . $stack . " !important;\n";
				$css .= 'line-height: ' . (string) $lh . " !important;\n";
				if ( 'body' === $elem_key ) {
					$css .= "letter-spacing: 0.03em !important;\ntext-align: justify;\nword-break: break-word;\noverflow-wrap: break-word;\n";
				} else {
					$css .= "letter-spacing: 0.02em !important;\n";
				}
				$css .= "}\n";
			}
		} else {
			$global_font  = $settings['font_family'] ?? 'monlam-bodtig-2';
			$global_stack = $this->get_font_stack( $global_font );
			$global_lh    = floatval( $settings['line_height'] ?? 2.3 );
			if ( $global_lh < 1.5 || $global_lh > 4.0 ) {
				$global_lh = 2.3;
			}
			$css .= "body.tb-lang-bo,\n";
			$css .= "body.tb-lang-bo p,\n";
			$css .= "body.tb-lang-bo li,\n";
			$css .= "body.tb-lang-bo a,\n";
			$css .= "body.tb-lang-bo blockquote,\n";
			$css .= "body.tb-lang-bo .entry-content,\n";
			$css .= "body.tb-lang-bo article,\n";
			$css .= ".tb-tibetan-text,\n";
			$css .= "[lang=\"bo\"],\n";
			$css .= "[lang=\"bo-CN\"],\n";
			$css .= "[lang=\"bo-IN\"] {\n";
			$css .= 'font-family: ' . $global_stack . " !important;\n";
			$css .= 'line-height: ' . (string) $global_lh . " !important;\n";
			$css .= "letter-spacing: 0.03em !important;\n";
			$css .= "text-align: justify;\n";
			$css .= "word-break: break-word;\n";
			$css .= "overflow-wrap: break-word;\n}\n";
			$css .= "body.tb-lang-bo h1,\nbody.tb-lang-bo h2,\nbody.tb-lang-bo h3,\n";
			$css .= "body.tb-lang-bo h4,\nbody.tb-lang-bo h5,\nbody.tb-lang-bo h6,\n";
			$css .= "body.tb-lang-bo .entry-title {\n";
			$css .= 'font-family: ' . $global_stack . " !important;\n";
			$css .= 'line-height: ' . (string) round( $global_lh * 0.9, 2 ) . " !important;\n";
			$css .= "letter-spacing: 0.02em !important;\n}\n";
		}
		wp_add_inline_style( 'tmt-frontend-css', $css );
	}

	/**
	 * Get CSS Font Stack from Key
	 *
	 * @param string $key Font key.
	 * @return string
	 */
	public function get_font_stack( string $key ): string {
		$registry = $this->get_font_registry();
		if ( isset( $registry[ $key ]['stack'] ) ) {
			return $registry[ $key ]['stack'];
		}
		return "'Monlam Uni OuChan 2', 'Monlam Uni OuChan', 'Noto Serif Tibetan', 'Microsoft Himalaya', 'Kailash', serif";
	}
}
