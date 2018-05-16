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
    Button
} from 'reactstrap';
import OverlayLoader from 'react-loading-indicator-overlay/lib/OverlayLoader'

const WAIT_INTERVAL = 800;

class App extends React.Component {
	constructor(props) {
		super(props);
		this.state = {
			loading: false,
			pukiwiki: "",
			markdown: "",
			timer: null,
		};
		this.onTextChange = this.onTextChange.bind(this);
		this.triggerChange = this.triggerChange.bind(this);
	}

	onTextChange(event) {
		clearTimeout(this.state.timer);
		this.setState({
			pukiwiki: event.target.value
		});

        this.state.timer = setTimeout(this.triggerChange, WAIT_INTERVAL);
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
                        <Col md="6">
                            <Form>
                                <FormGroup>
                                    <Label for="pukiwiki">Pukiwiki</Label>
                                    <Input type="textarea" name="text" id="pukiwiki" rows="20" onChange={this.onTextChange} value={this.state.pukiwiki} />
                                </FormGroup>
                            </Form>
                        </Col>
                        <Col md="6">
                            <Form>
                                <FormGroup>
                                    <Label for="markdown" className="mr-2">
                                        Markdown
                                    </Label>
                                    <Button size="sm" outline color="info">内容をクリップボードにコピー</Button>
                                    <OverlayLoader
                                        color={'blue'} // default is white
                                        loader="ScaleLoader" // check below for more loaders
                                        text="Loading..."
                                        active={this.loading}
                                        backgroundColor={'black'} // default is black
                                        opacity=".7" // default is .9
                                        style={{borderRadius: '.25rem'}}
                                        >
                                        <Input type="textarea" name="text" id="markdown" rows="20" value={this.state.markdown} onChange={() => {}} />
                                    </OverlayLoader>
                                </FormGroup>
                            </Form>
                        </Col>
                    </Row>
                </Container>
            </div>
        )
    }
}

export default App;