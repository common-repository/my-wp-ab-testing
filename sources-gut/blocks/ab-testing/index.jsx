
import SettingsTestingEdit from '../../components/ab-testing/index.jsx';
(function (wp) {
    const {registerBlockType} = wp.blocks;
    const {__} = wp.i18n;
    const {Fragment} = wp.element;


    registerBlockType('reblexab/ab-testing', {
        title: __('A/B Testing', 'blocks-and-me'),
        icon: {src: 'chart-pie'},
        category: 'common',
        attributes: {
            reblexab_id: {
                type: 'string',
                default:0
            },
        },
        supports: {
            className: false
        },
        edit({attributes, className, setAttributes, isSelected}) {
            const {reblexab_id} = attributes;

            return (
                <Fragment>
                <SettingsTestingEdit
                    {...{
                        reblexab_id,
                        setAttributes,
                        attributes,
                        className
                    }} />
                </Fragment> 
            )
        },
        save({attributes, className, setAttributes, isSelected}) {
            return null
        },
    });


})(window.wp);