<?php

require_once __DIR__ . '/class-component-testcase.php';

use Apple_Exporter\Components\Video as Video;

class Video_Test extends Component_TestCase {

	public function testGeneratedJSON() {
		$workspace = $this->prophet->prophesize( '\Exporter\Workspace' );

		// Pass the mock workspace as a dependency
		$component = new Video( '<video><source src="http://someurl.com/video-file.mp4?some_query=string"></video>',
			$workspace->reveal(), $this->settings, $this->styles, $this->layouts );

		$json = $component->to_array();
		$this->assertEquals( 'video', $json['role'] );
		$this->assertEquals( 'http://someurl.com/video-file.mp4?some_query=string', $json['URL'] );
	}

	public function testFilter() {
		$workspace = $this->prophet->prophesize( '\Exporter\Workspace' );

		// Pass the mock workspace as a dependency
		$component = new Video( '<video><source src="http://someurl.com/video-file.mp4?some_query=string"></video>',
			$workspace->reveal(), $this->settings, $this->styles, $this->layouts );

		add_filter( 'apple_news_video_json', function( $json ) {
			$json['URL'] = 'http://filter.me';
			return $json;
		} );

		$json = $component->to_array();
		$this->assertEquals( 'http://filter.me', $json['URL'] );
	}

}
