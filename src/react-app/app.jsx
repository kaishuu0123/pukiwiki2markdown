import React from 'react';
import Header from './header.jsx';
import axios from 'axios';
import {
    Container,
    Row,
    Col,
    Form,
    FormGroup,
    Label,
    Input,
    Button,
    Alert
} from 'reactstrap';
import OverlayLoader from 'react-loading-indicator-overlay/lib/OverlayLoader'
import { CopyToClipboard } from 'react-copy-to-clipboard';
import Octicon from 'react-octicon';
import CustomLoader from './custom-loader';

const WAIT_INTERVAL = 800;
const DISPLAY_WAIT_INTERVAL = 3000;

class App extends React.Component {
	constructor(props) {
		super(props);
		this.state = {
			loading: false,
			pukiwiki: "",
			markdown: "",
            inputTimer: null,
            copyTimer: null,
            copied: false,
		};
		this.onTextChange = this.onTextChange.bind(this);
        this.triggerChange = this.triggerChange.bind(this);
        this.onCopy = this.onCopy.bind(this);
	}

	onTextChange(event) {
		clearTimeout(this.state.inputTimer);
		this.setState({
			pukiwiki: event.target.value
		});

        this.state.inputTimer = setTimeout(this.triggerChange, WAIT_INTERVAL);
        event.preventDefault();
    }

    onCopy() {
        clearTimeout(this.state.copyTimer);
		this.setState({
			copied: true
		});

        this.state.copyTimer = setTimeout(() => this.setState({copied: false}), DISPLAY_WAIT_INTERVAL);
        event.preventDefault();
	}

	triggerChange() {
		this.setState({
			loading: true,
		});

		axios.post('/api/v1/convert', {
			body: this.state.pukiwiki
		}).then(res => {
			this.setState({ loading: false, markdown: res.data.body})
		});
	}

    render() {
        return (
            <div>
                <Header />
                <Container fluid className="mt-2">
                    <Row>
                        <Col md="12" className="mb-2">
                            <div class="lead">
                                pukiwiki 表記のテキストを markdown 表記に変換します。<br />
                                主に <a href="https://growi.org">Growi (growi.org)</a> で解釈可能な markdown に変換することを目的にしています <br />
                            </div>
                        </Col>
                    </Row>
                    <Row>
                        <Col md="6">
                            <Form>
                                <FormGroup>
                                    <Label for="pukiwiki">
                                        <h4>Pukiwiki</h4>
                                    </Label>
                                    <Input type="textarea" name="text" id="pukiwiki" rows="20" onChange={this.onTextChange} value={this.state.pukiwiki} />
                                </FormGroup>
                            </Form>
                        </Col>
                        <Col md="6">
                            <Form>
                                <FormGroup>
                                    <Label for="markdown" className="mr-2">
                                        <h4>Markdown</h4>
                                    </Label>
                                    <CopyToClipboard text={this.state.markdown}
                                        onCopy={this.onCopy}>
                                        <Button size="sm" outline color="info"><Octicon name="clippy" /> 内容をクリップボードにコピー</Button>
                                    </CopyToClipboard>
                                    {this.state.copied ? <span className="ml-2" style={{color: 'green'}}>Copied.</span> : null}
                                    <CustomLoader
                                        active={this.state.loading}
                                        color={'#0E6EB8'}
                                        text="Loading... Please wait!"
                                        borderRadius="0.25em"
                                        backgroundColor="rgba(0,0,0,0.8)"
                                    >
                                        <Input type="textarea" name="text" id="markdown" rows="20" value={this.state.markdown} onChange={() => {}} />
                                    </CustomLoader>
                                </FormGroup>
                            </Form>
                        </Col>
                    </Row>
                    <Row>
                        <Col md="12">
                            <Alert color="info">
                                <h4 class="alert-heading">変換ルール</h4>
                                <ul>
                                    <li>ヘッダ、リスト(入れ子)、pre 表記などに対応</li>
                                    <li>テーブル表記は HTML タグに変換</li>
                                    <li>プラグインに対応していない場合には、「@@@@ xxx プラグインには対応していません。適宜対応してください @@@@」といったメッセージに置換</li>
                                </ul>
                            </Alert>
                        </Col>
                    </Row>
                    <hr />
                    <footer className="container-fluid">
                        © 2018 @kaishuu0123 (Twitter)
                    </footer>
                </Container>
            </div>
        )
    }
}

export default App;