import React from 'react';
import {
    Navbar,
    NavbarBrand
} from 'reactstrap';

class Header extends React.Component {
    constructor(props) {
        super(props);
    }

    render() {
        return (
            <div>
                <Navbar color="dark" dark>
                    <NavbarBrand href="/">pukiwiki2markdown</NavbarBrand>
                </Navbar>
            </div>
        )
    }
}

export default Header;