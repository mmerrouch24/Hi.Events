import {ActionIcon, NumberInput, NumberInputHandlers, Select, TextInputProps} from "@mantine/core";
import {useEffect, useRef, useState} from "react";
import {UseFormReturnType} from "@mantine/form";
import {IconMinus, IconPlus} from "@tabler/icons-react";
import classes from './NumberSelector.module.scss';
import classNames from "classnames";
import _ from "lodash";

interface NumberSelectorProps extends TextInputProps {
    formInstance: UseFormReturnType<any>;
    fieldName: string,
    min?: number;
    max?: number;
}

export const NumberSelector = ({formInstance, fieldName, min, max}: NumberSelectorProps) => {
    const handlers = useRef<NumberInputHandlers>(null);
    const minValue = min || 0;
    const maxValue = max || 100;
    const value = Number(_.get(formInstance.values, fieldName) ?? 0);

    useEffect(() => {
        if (Number.isNaN(value)) {
            formInstance.setFieldValue(fieldName, 0);
            return;
        }

        if (value > maxValue) {
            formInstance.setFieldValue(fieldName, maxValue);
            return;
        }

        if (value !== 0 && value < minValue) {
            formInstance.setFieldValue(fieldName, Math.min(minValue, maxValue));
        }
    }, [fieldName, formInstance, maxValue, minValue, value]);

    const setValue = (nextValue: number) => {
        formInstance.setFieldValue(fieldName, nextValue);
    };

    const increment = () => {
        if (value === 0 && minValue > 1) {
            setValue(Math.min(minValue, maxValue));
        } else if (value < maxValue) {
            setValue(value + 1);
        }
    };

    const decrement = () => {
        if (value > minValue) {
            setValue(value - 1);
        } else {
            setValue(0);
        }
    };

    const changeValue = (newValue: number) => {
        if (Number.isNaN(newValue)) {
            setValue(0);
            return;
        }

        const boundedValue = Math.max(0, Math.min(newValue, maxValue));
        setValue(boundedValue);
    };

    return (
        <div className={classNames(classes.wrapper, 'button-input')}>
            <ActionIcon
                size={28}
                onClick={decrement}
                disabled={value === 0}
                onMouseDown={(event) => event.preventDefault()}
                className={classes.control}
            >
                <IconMinus size="1rem" stroke={1.5}/>
            </ActionIcon>

            <NumberInput
                mb={0}
                variant="unstyled"
                min={minValue}
                max={maxValue}
                handlersRef={handlers}
                value={value}
                hideControls
                onChange={changeValue}
                classNames={{input: classes.input}}
            />

            <ActionIcon
                size={28}
                onClick={increment}
                disabled={value >= maxValue}
                onMouseDown={(event) => event.preventDefault()}
                className={classes.control}
            >
                <IconPlus size="1rem" stroke={1.5}/>
            </ActionIcon>
        </div>
    );
}

/* todo: create an event setting to choose select over button */
export const NumberSelectorSelect = ({formInstance, fieldName, min, max, className}: NumberSelectorProps) => {
    const [value, setValue] = useState<string>('0');

    const minValue = min || 0;
    const maxValue = max || 100;

    useEffect(() => {
        // Only synchronize with form if the value is within bounds and not 0
        if (value !== '0') {
            formInstance.setFieldValue(fieldName, value);
        }
    }, [value, formInstance, fieldName]);

    let data = Array.from({length: maxValue - minValue + 1}, (_, i) => ({
        label: String(minValue + i),
        value: String(minValue + i),
    }));

    if (minValue > 0) {
        data = [{label: '0', value: '0'}, ...data];
    }

    return (
        <div className={classNames(classes.wrapper, 'select-input')}>
            <Select
                classNames={{
                    input: classes.input,
                }}
                className={className}
                onChange={(value) => setValue(value ?? '0')} // Ensure the value is set correctly on change
                value={value}
                data={data}
                checkIconPosition="right"
            />
        </div>
    );
}
