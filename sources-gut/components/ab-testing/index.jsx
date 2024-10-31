const {__} = wp.i18n;
const {Component, Fragment} = wp.element;
const {InspectorControls} = wp.blockEditor;
const {PanelBody, SelectControl} = wp.components;

export default class SettingsTestingEdit extends Component {

    constructor(props) {
        super(props);

        this.state = {
            listItem: [],
            isOpen: false,
            abtesting_block: [],
            modal_data: [],
        };
        this.getAllAbtesting();

    }

    getAllAbtesting() {
        fetch(REST_API.url + `wp/v2/abtesting?per_page=100`)
            .then(response => response.json())
            .then(abtestingList => {

                let tmp = [];
                let tmpab = new Object();
                tmp.push(
                    {
                        value: 0, label: 'Choix',
                    }
                )
                abtestingList.map(item => {
                    tmp.push(
                        {
                            value: item.id, label: item.title.rendered,
                        }
                    )
                    tmpab[item.id] = {
                        abtesting_block_a: {
                            link: item.meta_abtesting.abtesting_block_a.link,
                            title: item.meta_abtesting.abtesting_block_a.title,
                            content: item.meta_abtesting.abtesting_block_a.content,
                            count: item.meta_abtesting.abtesting_block_a.count,
                            conversion: item.meta_abtesting.abtesting_block_a.conversion,
                            percentage: item.meta_abtesting.abtesting_block_a.percentage
                        },
                        abtesting_block_b: {
                            link: item.meta_abtesting.abtesting_block_b.link,
                            title: item.meta_abtesting.abtesting_block_b.title,
                            content: item.meta_abtesting.abtesting_block_b.content,
                            count: item.meta_abtesting.abtesting_block_b.count,
                            conversion: item.meta_abtesting.abtesting_block_b.conversion,
                            percentage: item.meta_abtesting.abtesting_block_b.percentage
                        }
                    }
                });

                this.setState({
                    listItem: tmp,
                    abtesting_block: tmpab,
                })

            })
    }

    closeModal(){
        this.setState({
            isOpen: false
        })
    };

    openModal(idBlock, modalId){
        const {attributes, setAttributes} = this.props;
        const {reblexab_id} = attributes;

        let abtesting = this.state.abtesting_block[reblexab_id];

        let data = {
            title:'',
            link:'',
            id:0
        };
        if(modalId === 'testing-a') {
            data.title = abtesting.abtesting_block_a.title;
            data.link = abtesting.abtesting_block_a.link;
            data.content = abtesting.abtesting_block_a.content;
            data.count = abtesting.abtesting_block_a.content;
        } else {
            data.title = abtesting.abtesting_block_b.title;
            data.link = abtesting.abtesting_block_b.link;
            data.content = abtesting.abtesting_block_b.content;
            data.count = abtesting.abtesting_block_b.content;
        }

        this.setState({
            isOpen: true,
            modal_data: data
        })
    }
    componentDidUpdate(prevProps, prevState, snapshot) {
        this.loadScript();
    }



    loadScript() {
        $ = jQuery

        $( document ).find('.reblexab_chart').each( function( ) {
            var ctx = $( this );
            if ( ctx ) {
                var data_a = parseInt( $( ctx ).attr( 'data-a' ) );
                var data_b = parseInt( $( ctx ).attr( 'data-b' ) );
                var data_title = $( ctx ).attr( 'data-title' );
                var data = {
                    labels: [ 'Block B', 'Block A' ],
                    datasets: [ {
                        data: [ data_b, data_a ],
                        backgroundColor: [ 'rgba( 51, 153, 204, 0.7 )', 'rgba( 255, 52, 117, 0.7 )' ],
                    } ]
                }
                var chart = new Chart( ctx, {
                    type: 'pie',
                    data: data,
                    options: {
                        title: {
                            display: true,
                            text: data_title,
                        },
                        legend: {
                            display: true,
                            position: 'bottom',
                            rtl: true,
                        },
                    }
                } );
            }
        } )
    }

    render() {
        const {attributes, setAttributes, isSelected} = this.props;
        const {reblexab_id} = attributes;

        return (
            <Fragment>
                <InspectorControls>
                    <PanelBody>
                        <SelectControl
                            label="Block"
                            value={reblexab_id}
                            options={this.state.listItem}
                            onChange={newValue => setAttributes({reblexab_id: newValue})}
                        />
                    </PanelBody>
                </InspectorControls>
                <h2>{__( 'A/B Testing live results', 'reblexab' )}</h2>
                {reblexab_id == 0 &&
                   <Fragment>
                       <p style={{textAlign: 'center'}}>{__('Select Campaign in right panel')}</p>
                       <span className="dashicons dashicons-chart-pie" style={{display: 'block',margin: '0 auto',width: '200px',height: '110px',fontSize: '100px'}}></span>
                   </Fragment>
                }
                {this.state.abtesting_block && this.state.abtesting_block[reblexab_id] &&
                    <Fragment>
                        <table style={{display: 'table-caption'}} className="wp-list-table widefat reblexab_stats_wrapper_charts">
                           <tr>
                               <td>
                                   <canvas
                                       id="reblexab_chart_displayed"
                                       className="reblexab_chart"
                                       data-title="Displays"
                                       data-a={this.state.abtesting_block[reblexab_id].abtesting_block_a.count}
                                       data-b={this.state.abtesting_block[reblexab_id].abtesting_block_b.count}
                                   ></canvas>
                               </td>
                               <td>
                                   <canvas
                                       id="reblexab_chart_converted"
                                       className="reblexab_chart"
                                       data-title="Conversions"
                                       data-a={this.state.abtesting_block[reblexab_id].abtesting_block_a.conversion}
                                       data-b={this.state.abtesting_block[reblexab_id].abtesting_block_b.conversion}
                                   ></canvas>
                               </td>
                           </tr>
                       </table>
                        <table className="wp-list-table widefat fixed striped reblexab_stats_wrapper_table">
                            <tr>
                                <th>{__( 'Block', 'reblexab' )}</th>
                                <th>{__('Displayed', 'reblexab')}</th>
                                <th>{__( 'Converted', 'reblexab' )}</th>
                                <th>{__( 'Conversion rate', 'reblexab' )}</th>
                            </tr>
                            <tr>
                                <th><a target='_blank' href={this.state.abtesting_block[reblexab_id].abtesting_block_a.link}>{__('Edit block A', 'reblexab')}</a></th>
                                <td>{this.state.abtesting_block[reblexab_id].abtesting_block_a.count}</td>
                                <td>{this.state.abtesting_block[reblexab_id].abtesting_block_a.conversion}</td>
                                <td>
                                    <strong>{this.state.abtesting_block[reblexab_id].abtesting_block_a.percentage}</strong>
                                </td>
                            </tr>
                            <tr>
                                <th><a target='_blank' href={this.state.abtesting_block[reblexab_id].abtesting_block_b.link}>{__('Edit block B', 'reblexab')}</a></th>
                                <td>{this.state.abtesting_block[reblexab_id].abtesting_block_b.count}</td>
                                <td>{this.state.abtesting_block[reblexab_id].abtesting_block_b.conversion}</td>
                                <td>
                                    <strong>{this.state.abtesting_block[reblexab_id].abtesting_block_b.percentage}</strong>
                                </td>
                            </tr>
                        </table>
                    </Fragment>
                }
            </Fragment>
        )
    }

}