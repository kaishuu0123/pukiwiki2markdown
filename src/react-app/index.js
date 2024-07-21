import React from 'react';
import { render } from 'react-dom';

// import store from './store';
// import AppRouter from './components/AppRouter';

// import 'purecss/build/pure.css';
// import "../stylesheet/index.scss";

import 'bootstrap/dist/css/bootstrap.min.css';
import App from './app';

console.log('hello react');

render(
  <App />,
  document.getElementById('app')
);
