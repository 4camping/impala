import axios from 'axios'
import Cropper from 'react-cropper'
import Datetime from 'react-datetime'
import {Draggable, Droppable} from 'react-drag-and-drop'
import Dropzone from 'react-dropzone'
import React, {Component} from 'react'
import request from 'sync-request'

var LINKS = {}
var ROW = 'row'
var SNAPSHOTS = {}
var VALIDATORS = 'validators'

export default class Form extends Component {
    constructor(props){
        super(props);
        var name = this.constructor.name;
        this.state = JSON.parse(document.getElementById(name[0].toLowerCase() + name.substring(1, name.length)).getAttribute('data'))
        LINKS = JSON.parse(document.getElementById(name[0].toLowerCase() + name.substring(1, name.length)).getAttribute('data-links'))
    }
    attached() {
        var body = [];
        for (var key in this.state[ROW]) {
            var closure = this[this.state[ROW][key].Method]
            if('function' == typeof(closure)) {
                body.push(this[this.state[ROW][key].Method](key))
            }
        }
        return body;
    }
    addAction(key) {
        return <a key={key}
                  href={this.state[ROW][key].Attributes.href}
                  style={this.state[ROW][key].Attributes.style}
                  className={this.state[ROW][key].Attributes.className}
                  onClick={this.bind(this.state[ROW][key].Attributes.onClick)}>{this.state[ROW][key].Label}</a>
    }
    addButton(key) {
        console.log('Add button method is suppose to be overloaded by children component.')
    }
    addCheckbox(key) {
        return <div key={key} style={this.state[ROW][key].Attributes.style}>
                    <input checked={this.state[ROW][key].Attributes.checked}
                           id={key}
                           onChange={this.change.bind(this)}
                           type='checkbox'
                           value={this.state[ROW][key].Attributes.value}  />
                    <label style={{marginLeft:'10px'}}>{this.state[ROW][key].Label}</label>
        </div>
    }
    addDateTime(key) {
        return <Datetime locale={this.state[ROW][key].Attributes.locale}
                         onChange={this.datetime.bind(this, key)}
                         value={this.state[ROW][key].Attributes.value}
            />
    }
    addGallery(key) {
        var container = []
        for(var photo in this.state[ROW][key].Attributes.data) {
            var crop = ''
            if(this.state[ROW][key].Attributes.data[photo].size.height != this.state[ROW][key].Attributes.data[photo].crop.height ||
            this.state[ROW][key].Attributes.data[photo].size.width != this.state[ROW][key].Attributes.data[photo].crop.width) {
                crop = <a className='label label-info'
                        id={photo}
                        name={key}
                        onClick={this.crop.bind(this)}>
                        <span className='glyphicon glyphicon-save-file'></span>&nbsp;&nbsp;{this.state[ROW][key].Attributes.crop}</a>
            }
            container.push(<div className='card' key={'gallery-' + key + '-' + photo} style={{height:'140px',width:'20rem',float:'left',margin:'10px'}}>
                <Draggable data={photo} type={key}>
                    <Droppable accept='image/*'
                        types={[key]}
                        onDrop={this.move.bind(this, photo)}>
                        {this.image(key, photo)}
                    </Droppable>
                </Draggable>
                <div className='card-body'>
                    <a className='label label-danger'
                        id={photo}
                        name={key}
                        onClick={this.delete.bind(this)}>
                        <span className='glyphicon glyphicon-remove'></span>&nbsp;&nbsp;{this.state[ROW][key].Attributes.delete}</a>
                    {crop}            
                </div>
            </div>)

        }
        return <div className={this.state[ROW][key].Attributes.className}
                    id={key}
                    key={key}
                    onBlur={this.bind(this.state[ROW][key].Attributes.onBlur)}
                    onClick={this.bind(this.state[ROW][key].Attributes.onClick)}
                    onChange={this.change.bind(this)}
                    style={this.state[ROW][key].Attributes.style} >
            <div><label>{this.state[ROW][key].Label}</label></div>
            {container}
            <div style={{clear:'both'}}></div>
        </div>
    }
    addHidden(key) {
        return <input key={key} type='hidden' />
    }
    addMessage(key) {
        return <div key={key} 
                    className='alert alert-success'
                    role='alert'
                    style={this.state[ROW][key].Attributes.style}>
                    {this.state[ROW][key].Label}</div>
    }
    addMultiSelect(key) {
        return <div key={key}><label>{this.state[ROW][key].Label}</label>
            <select className={this.state[ROW][key].Attributes.className}
                       id={this.state[ROW][key].Attributes.id}
                       multiple
                       style={this.state[ROW][key].Attributes.style}
                       onChange={this.change.bind(this)}>{this.getOptions(key)}>
        </select></div>
    }
    addProgressBar(key) {
        return <div key={key}
            style={this.state[ROW][key].Attributes.style}
            className='progress'><div
            className='progress-bar'
            style={{width:this.state[ROW][key].Attributes.width+'%'}}></div></div>
    }
    addRadioList(key) {
        var container = [];
        var options = this.state[ROW][key].Attributes.data;
        container.push(<div>{this.addValidator(key)}</div>);
        for (var value in options) {
            container.push(<div key={value}><input name={key} 
                                    onClick={this.bind(this.state[ROW][key].Attributes.onClick)}
                                    type='radio'
                                    value={value} />
                                    <label>{this.state[ROW][key].Attributes.data[value]}</label></div>);
        }
        return container;
    }
    addSelect(key) {
        return <div key={key}>
                <label>{this.state[ROW][key].Label}</label>
                <select className={this.state[ROW][key].Attributes.className}
                                      defaultValue={this.state[ROW][key].Attributes.value}
                                      id={key}
                                      style={this.state[ROW][key].Attributes.style}
                                      onChange={this.change.bind(this)}>{this.getOptions(key)}
                </select>
                {this.addValidator(key)}
        </div>
    }
    addSubmit(key) {
        return <input
            className={this.state[ROW][key].Attributes.className}
            data={this.state[ROW][key].Attributes.data}
            id={key}
            key={key}
            onClick={this.bind(this.state[ROW][key].Attributes.onClick)}
            style={this.state[ROW][key].Attributes.style}
            type='submit'
            value={this.state[ROW][key].Label} />
    }
    addUpload(key) {
        var files = []
        for(var file in this.state[ROW][key].Attributes.value) {
            var id = key + file
            files.push(<li key={id} className='list-group-item'>{this.state[ROW][key].Attributes.value[file].name}<i className='fa fa-spinner fa-spin' style={{float:'right'}}></i></li>)
        }
        return <div id={key} key={key} style={this.state[ROW][key].Attributes.style}>
                <Dropzone onDrop={this.drop.bind(this, key)}
                          multiple={true}
                          style={{height:'50px',borderWidth:'2px',borderColor:'rgb(102, 102, 102)',borderStyle:'dashed',borderRadius:'5px'}}>
                    <center>{this.state[ROW][key].Label}</center>
                </Dropzone>
                <ul className='list-group'>{files}</ul>
                {this.addValidator(key)}
            </div>
    }
    addValidator(key) {
        if(null == this.state[VALIDATORS][key]) { } else {
            return <div key={'validator-' + key} className='bg-danger'>{this.state[VALIDATORS][key]}</div>
        }
    }
    addText(key) {
        return <div key={key}>
            <label>{this.state[ROW][key].Label}</label>
            <input 
            id={key}
            className={this.state[ROW][key].Attributes.className}
            data={this.state[ROW][key].Attributes.data}
            onBlur={this.bind(this.state[ROW][key].Attributes.onBlur)}
            onClick={this.bind(this.state[ROW][key].Attributes.onClick)} 
            onChange={this.change.bind(this)}
            readOnly={this.state[ROW][key].Attributes.readonly}
            style={this.state[ROW][key].Attributes.style}
            type={this.state[ROW][key].Attributes.type}
            value={this.state[ROW][key].Attributes.value} />
            <div>{this.addValidator(key)}</div></div>
    }
    addTextArea(key) {
        return <div key={key} className='input-group'>
            <textarea
                id={key}
                className={this.state[ROW][key].Attributes.className}
                data={this.state[ROW][key].Attributes.data}
                onBlur={this.bind(this.state[ROW][key].Attributes.onBlur)}
                onClick={this.bind(this.state[ROW][key].Attributes.onClick)}
                onChange={this.change.bind(this)}
                style={this.state[ROW][key].Attributes.style}>
                {this.state[ROW][key].Attributes.value}</textarea>
            <div>{this.addValidator(key)}</div></div>
    }
    addTitle(key) {
        return <h1 key={key} className={this.state[ROW][key].Attributes.className}>{this.state[ROW][key].Attributes.value}</h1>
    }
    bind(method) {
        if(undefined === method) {
            return
        }
        var closure = method.replace(/\(/, '').replace(/\)/, '')
        if('function' == typeof(this[closure])) {
            return this[closure].bind(this)
        }
    }
    change(event) {
        if('checkbox' == event.target.type && 1 == event.target.value) {
            var element = this.state[ROW][event.target.id]
            element.Attributes.value = 0
            element.Attributes.checked = null
        } else if('checkbox' == event.target.type) {
            var element = this.state[ROW][event.target.id]
            element.Attributes.value = 1
            element.Attributes.checked = 'checked'
        } else {
            var element = this.state[ROW][event.target.id]
            element.Attributes.value = event.target.value
        }
        var state = []
        state[event.target.id] = element
        this.setState(state)
    }
    crop(event) {
        if(false === confirm(this.state[ROW][event.target.name].Attributes.content)) {
            return
        }
        var data = {}
        data.image = {key: event.target.name, photo: event.target.id, snapshot: SNAPSHOTS[event.target.name][event.target.id]}
        data.row = this.state[ROW]
        var state = []
        state[ROW] = JSON.parse(request('POST', LINKS.crop, { json: data }).getBody('utf8'))
        this.setState(state)
    }
    snapshot(form, key, photo, event) {
        if(undefined == SNAPSHOTS[key]) {
            SNAPSHOTS[key] = {}
            SNAPSHOTS[key][photo] = {}
        } else if(undefined == SNAPSHOTS[key][photo]) {
            SNAPSHOTS[key][photo] = {}
        }
        /*for(var attribute in event.target) {
            if('cropper' == attribute) {
                var cropper = event.target[attribute];
                break;
            }
        }*/
        SNAPSHOTS[key][photo].x = event.detail.x
        SNAPSHOTS[key][photo].width = event.detail.width
        SNAPSHOTS[key][photo].height = event.detail.height
        SNAPSHOTS[key][photo].y = event.detail.y
    }
    image(key, photo) {
        if(this.state[ROW][key].Attributes.data[photo].size.height == this.state[ROW][key].Attributes.data[photo].crop.height &&
            this.state[ROW][key].Attributes.data[photo].size.width == this.state[ROW][key].Attributes.data[photo].crop.width) {
            return <img alt={this.state[ROW][key].Attributes.data[photo].alt}
                    className='card-img-top'
                    height={this.state[ROW][key].Attributes.data[photo].height}
                    id={photo}
                    name={key}
                    src={this.state[ROW][key].Attributes.data[photo].src + '?t=' + new Date().getTime()}
                    width={this.state[ROW][key].Attributes.data[photo].width} />
        } else {
            return <Cropper alt={this.state[ROW][key].Attributes.data[photo].alt.toString()}
                    ref='cropper'
                    src={this.state[ROW][key].Attributes.data[photo].src + '?t=' + new Date().getTime()}
                    style={{height:this.state[ROW][key].Attributes.data[photo].height,width:this.state[ROW][key].Attributes.data[photo].width}}
                    aspectRatio={this.state[ROW][key].Attributes.data[photo].crop.width / this.state[ROW][key].Attributes.data[photo].crop.height}
                    guides={false}
                    crop={this.snapshot.bind(null, this, key, photo)} />
        }
    }
    datetime(key, event) {
        var state = []
        state[key] = this.state[ROW][key]
        state[key].Attributes.value = event.format(this.state[ROW][key].Attributes.format.toUpperCase())
        this.setState(state)
    }
    delete(event) {
        if(false === confirm(this.state[ROW][event.target.name].Attributes.content)) {
            return
        }
        var data = {}
        data.image = {photo: event.target.id, key: event.target.name}
        data.row = this.state[ROW]
        var state = []
        state[ROW] = JSON.parse(request('POST', LINKS.delete, { json: data }).getBody('utf8'))
        this.setState(state)
    }
    drop(key, files) {
        var state = []
        state[ROW] = this.state[ROW]
        state[ROW][key].Attributes.value = files
        state[ROW][key].Attributes.type = true
        if(null == files[0].type.match('image')) {
            state[ROW][key].Attributes.type = false            
        }
        state[ROW][key].Attributes.content = 0
        this.setState(state)
        this.save(key)
    }
    done(payload) {
        var response = JSON.parse(request('POST', LINKS.done, { json: payload }).getBody('utf8'))
        var state = []
        for (var key in this.state[ROW]) {
            var element = this.state[ROW][key]
            element.Attributes.style = {display:'none'}
            state[key] = element
        }
        this.setState(state)
        return response
    }
    getOptions(key) {
        var container = []
        var options = this.state[ROW][key].Attributes.data
        for (var value in options) {
            if(this.state[ROW][key].Attributes.value == value) {
                container.push(<option selected key={value} value={value}>{this.state[ROW][key].Attributes.data[value]}</option>)
            } else {
                container.push(<option key={value} value={value}>{this.state[ROW][key].Attributes.data[value]}</option>)
            }
        }
        return container
    }
    load(key, file) {
        var response = JSON.parse(request('POST', LINKS.save, {json:{key:key,row:this.state[ROW]}}).getBody('utf8'))
        request('POST', LINKS.put, {json:{image:this.state[ROW][key].Attributes.type,file:file,name:response.file}})
        var row = JSON.parse(request('POST', LINKS.resize, {json:{key:key,row:response.row,name:response.file}}).getBody('utf8')).row
        var state = []
        state[ROW] = row
        state[ROW][key].Attributes.content++
        this.setState(state)
    }
    move(to, data) {
        for(var key in data) { var from = data[key]; break; }
        var state = []
        state[ROW] = this.state[ROW]
        var origin = state[ROW][key].Attributes.data[from]
        state[ROW][key].Attributes.data[from] = state[ROW][key].Attributes.data[to]
        state[ROW][key].Attributes.data[to] = origin
        axios.post(LINKS.move, {image:{key:key,from:from,to:to},row:state[ROW]})
        this.setState(state)
    }
    save(key) {
        var self = this
        if(this.state[ROW][key].Attributes.value.length > this.state[ROW][key].Attributes.content && false == this.state[ROW][key].Attributes.type) {
            var file = this.state[ROW][key].Attributes.value[this.state[ROW][key].Attributes.content]
            axios.get(file.preview).then(response => {
                self.load(key, response.data)
                self.save(key)
            })
        } else if(this.state[ROW][key].Attributes.value.length > this.state[ROW][key].Attributes.content) {
            var reader = new FileReader()
            var file = this.state[ROW][key].Attributes.value[this.state[ROW][key].Attributes.content]
            reader.onload = function() {
                self.load(key, reader.result)
                self.save(key)
            }
            reader.readAsDataURL(file)
        } else {
            var state = []
            state[ROW] = this.state[ROW]
            state[ROW][key].Attributes.value = []
            state[ROW][key].Attributes.content = 0
            this.setState(state)
        }
    }
    prepare(event) {
        var response = JSON.parse(request('POST', LINKS.prepare, { json: this.state }).getBody('utf8'))
        this.run(response, event.target.id + '-progress')
    }
    run(payload, progress) {
        if(parseInt(payload.stop) > parseInt(payload.offset)) {
            axios.post(LINKS.run, payload).then(response => {  this.run(response.data, progress) })
            var element = this.state[ROW][progress]
            element.Attributes.width = payload.offset / (payload.stop / 100)
            var state = []
            state[ROW] = this.state[ROW]
            state[ROW][progress] = element
            this.setState(state)
        } else {
            this.done(payload)
        }
    }
    submit() {
        var data = this.validate()
        if(null != data) {
            request('POST', LINKS.submit, { json: {row:data} })
            return true
        }
        return false
    }
    validate() {
        var data = new Object()
        for(var key in this.state[ROW]) {
            data[key] = this.state[ROW][key].Attributes.value
        }
        var state = []
        state[VALIDATORS] = JSON.parse(request('POST', LINKS.validate, {json: {row:data}}).getBody('utf8'))
        for (var validator in state[VALIDATORS]) {
            this.setState(state)
            return null
        }
        return data
    }
}