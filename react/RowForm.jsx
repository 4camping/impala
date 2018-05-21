import Form from './Form.jsx'
import React, {Component} from 'react'
import ReactDOM from 'react-dom'

var LINKS = {}
var ROW = 'row'

export default class RowForm extends Form {
    constructor(props){
        super(props)
        var name = this.constructor.name;
        LINKS = JSON.parse(document.getElementById(name[0].toLowerCase() + name.substring(1, name.length)).getAttribute('data-links'));
    }
    submit() {
        if(super.submit()) {
            var state = []
            state[ROW] = this.state[ROW]
            state[ROW]._message.Attributes.style.display = 'block'
            this.setState(state)
        }
    }
    render() {
        return <div>{this.attached()}</div>
    }
}
ReactDOM.render(<RowForm />, document.getElementById('rowForm'))